#!/usr/bin/env bash
# -*- coding: utf-8 -*-
#
# =============================================================================
#
# The MIT License (MIT)
#
# Copyright (c) 2025- Limetech
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.
#
# =============================================================================
#
# Author(s):
#   Simon Fairweather based on code from: Andre Richter, <andre.o.richter @t gmail_com>
#
# =============================================================================
#
# This script takes three parameters:
#   <Domain:Bus:Device.Function> i.e. dddd:bb:dd.f
#   <Vendor:Device> i.e. vvvv:dddd
#   <Number of VFs
# and then:
#
#  (1) If both <Vendor:Device> and <Domain:Bus:Device.Function> were provided,
#      validate that the requested <Vendor:Device> exists at <Domain:Bus:Device.Function>
#
#  (2) Set nu to value supplied as 3rd parameter:
#

BDF_REGEX="^[[:xdigit:]]{2}:[[:xdigit:]]{2}.[[:xdigit:]]$"
DBDF_REGEX="^[[:xdigit:]]{4}:[[:xdigit:]]{2}:[[:xdigit:]]{2}.[[:xdigit:]]$"
VD_REGEX="^[[:xdigit:]]{4}:[[:xdigit:]]{4}$"

if [[ $EUID -ne 0 ]]; then
    echo "Error: This script must be run as root" 1>&2
    exit 1
fi

if [[ -z "$@" ]]; then
    echo "Error: Please provide Domain:Bus:Device.Function (dddd:bb:dd.f) and/or Vendor:Device (vvvv:dddd)" 1>&2
    exit 1
fi

# Check that 3 parameters are supplied
if [[ $# -ne 3 ]]; then
    echo "Error: Expected 3 parameters, but got $#." 1>&2
    echo "Usage: $0 <param1> <param2> <param3>" 1>&2
    echo "Example: $0 0000:01:00.0 10de:1fb8 numvfs" 1>&2
    exit 1
fi

unset VD BDF MAC
for arg in "$@"
do
    if [[ $arg =~ $VD_REGEX ]]; then
        VD=$arg
    elif [[ $arg =~ $DBDF_REGEX ]]; then
        BDF=$arg
    elif [[ $arg =~ $BDF_REGEX ]]; then
        BDF="0000:${arg}"
        echo "Warning: You did not supply a PCI domain, assuming ${BDF}" 1>&2
    else
        # Treat as 3rd parameter (not a PCI ID)
        if [[ -z $MAC ]]; then
            MAC=$arg
        else
            echo "Error: Unrecognized argument '$arg'" 1>&2
            exit 1
        fi
    fi
done

TARGET_DEV_SYSFS_PATH="/sys/bus/pci/devices/$BDF"

if [[ ! -d $TARGET_DEV_SYSFS_PATH ]]; then
    echo "Error: Device ${BDF} does not exist, unable to action VFs setting" 1>&2
    exit 1
fi

if [[ ! -d "$TARGET_DEV_SYSFS_PATH/iommu/" ]]; then
    echo "Error: No signs of an IOMMU. Check your hardware and/or linux cmdline parameters. Use intel_iommu=on or iommu=pt iommu=1" 1>&2
    exit 1
fi

# validate that the correct Vendor:Device was found for this BDF
if [[ ! -z $VD ]]; then
    if [[ $(lspci -n -s ${BDF} -d ${VD} 2>/dev/null | wc -l) -eq 0 ]]; then
        echo "Error: Vendor:Device ${VD} not found at ${BDF}, unable to action VFs setting" 1>&2
        exit 1
    else
        echo "Vendor:Device ${VD} found at ${BDF}"
    fi
else
    echo "Warning: You did not specify a Vendor:Device (vvvv:dddd), unable to validate ${BDF}" 1>&2
fi



printf "\nSetting...\n"

: '
# Capture stderr output from echo into a variable
error_msg=$( (echo "$NUMVFS" > "$TARGET_DEV_SYSFS_PATH/sriov_numvfs") 2>&1 )

if [[ $? -ne 0 ]]; then
    echo "Error: Failed to set sriov_numvfs at $TARGET_DEV_SYSFS_PATH" >&2
    clean_msg=$(echo "$error_msg" | sed -n 's/.*error: \(.*\)/\1/p')
    echo "System message: $clean_msg" >&2
    exit 1
fi

printf "\n"


echo "Device ${VD} at ${BDF} set numvfs to ${NUMVFS}"
'

# Locate PF device

VF_PCI=$BDF
VF_PATH="/sys/bus/pci/devices/$VF_PCI"
PF_PATH=$(readlink -f "$VF_PATH/physfn" 2>/dev/null)

if [ ! -d "$PF_PATH" ]; then
    echo "Error: No PF found for VF $VF_PCI"
    exit 1
fi

# Determine PF interface name
PF_IFACE=$(basename "$(readlink -f "$PF_PATH/net"/* 2>/dev/null)")
if [ -z "$PF_IFACE" ]; then
    PF_IFACE=$(basename "$(ls -d /sys/class/net/*/device 2>/dev/null | grep "$PF_PATH" | head -n1 | cut -d/ -f5)")
fi

if [ -z "$PF_IFACE" ]; then
    echo "Error: Could not determine PF interface for $VF_PCI"
    exit 1
fi


# Determine VF index
VF_INDEX=""
for vfdir in /sys/class/net/$PF_IFACE/device/virtfn*; do
    [ -e "$vfdir" ] || continue
    vf_pci=$(basename "$(readlink -f "$vfdir")")
    if [ "$vf_pci" = "$VF_PCI" ]; then
        VF_INDEX=${vfdir##*/virtfn}
        break
    fi
done

if [ -z "$VF_INDEX" ]; then
    echo "Error: VF index not found for $VF_PCI under PF $BDF"
    exit 1
fi

echo "Setting MAC for VF:"
echo "  PF: $PF_IFACE"
echo "  VF Index: $VF_INDEX"
echo "  PCI: $VF_PCI"
echo "  MAC: $MAC"

# Determine current driver for this VF
VF_DRIVER=$(basename "$(readlink -f "$VF_PATH/driver" 2>/dev/null)")

# Unbind VF from driver if loaded
if [ -n "$VF_DRIVER" ]; then
    echo "Unbinding VF from driver $VF_DRIVER..."
    echo "$VF_PCI" > "/sys/bus/pci/drivers/$VF_DRIVER/unbind"
fi

# Set MAC
#echo "$PF_IFACE" vf "$VF_INDEX" mac "$MAC"
ip link set "$PF_IFACE" vf "$VF_INDEX" mac "$MAC"

# Rebind VF to driver if it was bound before
if [ -n "$VF_DRIVER" ]; then
    echo "Rebinding VF to driver $VF_DRIVER..."
    echo "$VF_PCI" > "/sys/bus/pci/drivers/$VF_DRIVER/bind"
fi

echo "Done."
