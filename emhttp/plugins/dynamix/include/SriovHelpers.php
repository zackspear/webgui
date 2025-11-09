<?PHP
/* Copyright 2005-2025, Lime Technology
 * Copyright 2012-2025, Bergware International.
 *
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */
?>
<?

#$allowedPCIClass = ['0x02','0x03'];
$allowedPCIClass = ['0x02'];

/**
 * Enumerate SR-IOV capable PCI devices (keyed by PCI address).
 *
 * Example JSON:
 * {
 *   "0000:03:00.0": {
 *     "class": "Ethernet controller",
 *     "class_id": "0x0200",
 *     "name": "Intel Corporation X710 for 10GbE SFP+",
 *     "driver": "i40e",
 *     "module": "i40e",
 *     "vf_param": "max_vfs",
 *     "total_vfs": 64,
 *     "num_vfs": 8,
 *     "vfs": [
 *       {"pci": "0000:03:10.0", "iface": "enp3s0f0v0", "mac": "52:54:00:aa:00:01"}
 *     ]
 *   }
 * }
 */

function getSriovInfoJson(bool $includeVfDetails = true): string {
    $results = [];
    $paths = glob('/sys/bus/pci/devices/*/sriov_totalvfs') ?: [];

    foreach ($paths as $totalvfFile) {
        $devdir = dirname($totalvfFile);
        $pci = basename($devdir);

        $total_vfs = (int) @file_get_contents($totalvfFile);
        $num_vfs   = (int) @file_get_contents("$devdir/sriov_numvfs");

        // Driver/module detection
        $driver = $module = $vf_param = null;
        $driver_link = "$devdir/driver";
        if (is_link($driver_link)) {
            $driver = basename(readlink($driver_link));
            $module_link = "$driver_link/module";
            $module = is_link($module_link) ? basename(readlink($module_link)) : $driver;
            $vf_param = detectVfParam($driver);
        }

        // Device class + numeric class + name
        [$class, $class_id, $name] = getPciClassNameAndId($pci);

        // Virtual functions
        $vfs = [];
        foreach (glob("$devdir/virtfn*") as $vf) {
            if (!is_link($vf)) continue;
            $vf_pci = basename(readlink($vf));
            $vf_entry = ['pci' => $vf_pci];

            if ($includeVfDetails) {
                // Vendor:Device formatted string
                $vendorFile = "/sys/bus/pci/devices/{$vf_pci}/vendor";
                $deviceFile = "/sys/bus/pci/devices/{$vf_pci}/device";
                $vendor = is_readable($vendorFile) ? trim(file_get_contents($vendorFile)) : null;
                $device = is_readable($deviceFile) ? trim(file_get_contents($deviceFile)) : null;
                $vf_entry['vd'] = ($vendor && $device) ? sprintf('%s:%s', substr($vendor, 2), substr($device, 2)) : null;

                // Network interface info
                $net = glob("/sys/bus/pci/devices/{$vf_pci}/net/*");
                if ($net && isset($net[0])) {
                    $iface = basename($net[0]);
                    $vf_entry['iface'] = $iface;
                    $macFile = "/sys/class/net/{$iface}/address";
                    if (is_readable($macFile)) {
                        $vf_entry['mac'] = trim(file_get_contents($macFile));
                    }
                }

                // IOMMU group
                $iommu_link = "/sys/bus/pci/devices/{$vf_pci}/iommu_group";
                if (is_link($iommu_link)) {
                    $vf_entry['iommu_group'] = basename(readlink($iommu_link));
                } else {
                    $vf_entry['iommu_group'] = null;
                }

                // --- Current driver ---
                $driver_link = "/sys/bus/pci/devices/{$vf_pci}/driver";
                if (is_link($driver_link)) {
                    $vf_entry['driver'] = basename(readlink($driver_link));
                } else {
                    $vf_entry['driver'] = null; // no driver bound
                }
            }
            $vfs[] = $vf_entry;
        }

        $results[$pci] = [
            'class' => $class,
            'class_id' => $class_id,
            'name' => $name,
            'driver' => $driver,
            'module' => $module,
            'vf_param' => $vf_param,
            'total_vfs' => $total_vfs,
            'num_vfs' => $num_vfs,
            'vfs' => $vfs
        ];
    }

    ksort($results, SORT_NATURAL);
    return json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

function detectVfParam(string $driver): ?string {
    if (!function_exists('shell_exec')) return null;
    $out = @shell_exec('modinfo ' . escapeshellarg($driver) . ' 2>/dev/null');
    if (!$out) return null;

    $lines = explode("\n", strtolower($out));
    $params = [];
    foreach ($lines as $line) {
        if (preg_match('/^parm:\s+(\S+)/', $line, $m)) $params[] = $m[1];
    }

    foreach (['max_vfs', 'num_vfs', 'sriov_numvfs', 'sriov_vfs'] as $key)
        if (in_array($key, $params, true)) return $key;

    foreach ($params as $p)
        if (preg_match('/vf/', $p)) return $p;

    return null;
}

/**
 * Robustly get PCI class (text + numeric ID) and device name from lspci/sysfs.
 */
function getPciClassNameAndId(string $pci): array {
    $class = 'Unknown';
    $class_id = null;
    $name = 'Unknown';

    // Numeric class code from sysfs
    $classFile = "/sys/bus/pci/devices/{$pci}/class";
    if (is_readable($classFile)) {
        $raw = trim(file_get_contents($classFile));
        $class_id = sprintf("0x%04x", (hexdec($raw) >> 8) & 0xFFFF);
    }

    // Try lspci -mm for machine-readable info
    $out = trim(@shell_exec('lspci -mm -s ' . escapeshellarg($pci) . ' 2>/dev/null'));
    if ($out && preg_match('/"([^"]+)"\s+"([^"]+)"\s+"([^"]+)"/', $out, $m)) {
        $class = $m[1];
        $name = trim($m[3]);
        return [$class, $class_id, $name];
    }

    // Fallback to regular lspci output
    $alt = trim(@shell_exec('lspci -s ' . escapeshellarg($pci) . ' 2>/dev/null'));
    if ($alt && preg_match('/^[\da-fA-F:.]+\s+([^:]+):\s+(.+)/', $alt, $m)) {
        $class = trim($m[1]);
        $name = trim($m[2]);
    }

    return [$class, $class_id, $name];
}

/**
 * Enumerate all VFs and group them by IOMMU group
 * Output: associative array or JSON with keys like "IOMMU group 29"
 */
function getVfListByIommuGroup(): array {
    $groups = [];

    foreach (glob('/sys/bus/pci/devices/*/physfn') as $vf_physfn) {
        $vf_dir = dirname($vf_physfn);
        $vf_pci = basename($vf_dir);

        $iommu_link = "$vf_dir/iommu_group";
        if (is_link($iommu_link)) {
            $iommu_group = basename(readlink($iommu_link));
        } else {
            $iommu_group = "unknown";
        }

        $groups[]  = "IOMMU group " . $iommu_group;
        $groups[] = $vf_pci;
    }

    ksort($groups, SORT_NATURAL);
    return $groups;
}

// ----------------------
// Parse SR-IOV VF counts
// ----------------------
function parseVFvalues() {
  $sriov_devices = [];
  $DBDF_SRIOV_REGEX = '/^[[:xdigit:]]{4}:[[:xdigit:]]{2}:[[:xdigit:]]{2}\.[[:xdigit:]]\|[[:xdigit:]]{4}:[[:xdigit:]]{4}\|[[:digit:]]+$/';
  if (is_file("/boot/config/sriov.cfg")) {
      $file = trim(file_get_contents("/boot/config/sriov.cfg"));
      $file = preg_replace('/^VFS=/', '', $file); // Remove prefix
      $entries = preg_split('/\s+/', $file, -1, PREG_SPLIT_NO_EMPTY);

      foreach ($entries as $entry) {
          if (preg_match($DBDF_SRIOV_REGEX, $entry)) {
              // Format: <DBDF>|<Vendor:Device>|<VF_count>
              [$dbdf, $ven_dev, $vf_count] = explode('|', $entry);
              $sriov_devices[$dbdf] = [
                  'dbdf'     => $dbdf,
                  'vendor'   => $ven_dev,
                  'vf_count' => (int)$vf_count,
              ];
          }
      }

    # $sriov_devices = array_values(array_unique($sriov_devices, SORT_REGULAR));
    return $sriov_devices;
  }
}

// ---------------------------------
// Parse SR-IOV VF settings (VFIO+MAC)
// ---------------------------------
function parseVFSettings() {
  $sriov_devices_settings = [];
  $DBDF_SRIOV_SETTINGS_REGEX = '/^[[:xdigit:]]{4}:[[:xdigit:]]{2}:[[:xdigit:]]{2}\.[[:xdigit:]]\|[[:xdigit:]]{4}:[[:xdigit:]]{4}\|[01]\|([[:xdigit:]]{2}:){5}[[:xdigit:]]{2}$/';
  if (is_file("/boot/config/sriovvfs.cfg")) {
      $file = trim(file_get_contents("/boot/config/sriovvfs.cfg"));
      $file = preg_replace('/^VFSETTINGS=/', '', $file); // Remove prefix
      $entries = preg_split('/\s+/', $file, -1, PREG_SPLIT_NO_EMPTY);

      foreach ($entries as $entry) {
          if (preg_match($DBDF_SRIOV_SETTINGS_REGEX, $entry)) {
              // Format: <DBDF>|<Vendor:Device>|<VFIO_flag>|<MAC>
              [$dbdf, $ven_dev, $vfio_flag, $mac] = explode('|', $entry);
              $sriov_devices_settings[$dbdf] = [
                  'dbdf'     => $dbdf,
                  'vendor'   => $ven_dev,
                  'vfio'     => (int)$vfio_flag,
                  'mac'      => strtoupper($mac),
              ];
          }
      }

    # $sriov_devices_settings = array_values(array_unique($sriov_devices_settings, SORT_REGULAR));
    return $sriov_devices_settings;
  }
}
?>