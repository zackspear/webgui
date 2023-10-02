#!/bin/bash
#
# rc.M		This file is executed by init(8) when the system is being
#		initialized for one of the "multi user" run levels (i.e.
#		levels 1 through 6).  It usually does mounting of file
#		systems et al.
#
# Version:	@(#)/etc/rc.d/rc.M  15.0  Fri Nov 12 18:51:28 UTC 2021
#
# Author:	Fred N. van Kempen, <waltje@uwalt.nl.mugnet.org>
#		Heavily modified by Patrick Volkerding <volkerdi@slackware.com>
#
# LimeTech - Modified for Unraid OS

# Tell the viewers what's going to happen.
echo "Going multiuser..."

# Update all the shared library links:
if [ -x /sbin/ldconfig ]; then
  echo "Updating shared library links:  /sbin/ldconfig &"
  /sbin/ldconfig &
fi

# Call the setterm init script to set screen blanking and power management
# defaults:
if [ -x /etc/rc.d/rc.setterm ]; then
  /etc/rc.d/rc.setterm
fi

# Set the hostname:
/bin/hostname $(cat /etc/HOSTNAME)

# Set the permissions on /var/log/dmesg according to whether the kernel
# permits non-root users to access kernel dmesg information:
if [ -r /proc/sys/kernel/dmesg_restrict ]; then
  if [ $(cat /proc/sys/kernel/dmesg_restrict) = 1 ]; then
    touch /var/log/dmesg
    chmod 640 /var/log/dmesg
  fi
else
  touch /var/log/dmesg
  chmod 644 /var/log/dmesg
fi
# Save the contents of 'dmesg':
/bin/dmesg -s 65536 > /var/log/dmesg

# Start the system logger.
if [ -x /etc/rc.d/rc.rsyslogd ]; then
  /etc/rc.d/rc.rsyslogd start
fi

# Update the X font indexes:
if [ -x /usr/bin/fc-cache ]; then
  echo "Updating X font indexes:  /usr/bin/fc-cache -f &"
  /usr/bin/fc-cache -f &
fi

# Run rc.udev again.  This will start udev if it is not already running
# (for example, upon return from runlevel 1), otherwise it will trigger it
# to look for device changes and to generate persistent rules if needed.
if grep -wq sysfs /proc/mounts && grep -q devtmpfs /proc/filesystems ; then
  if ! grep -wq nohotplug /proc/cmdline ; then
    if [ -x /etc/rc.d/rc.udev ]; then
      /etc/rc.d/rc.udev start
    fi
  fi
fi

# Initialize the networking hardware.
if [ -x /etc/rc.d/rc.inet1 ]; then
  /etc/rc.d/rc.inet1
fi

# Start D-Bus:
if [ -x /etc/rc.d/rc.messagebus ]; then
  /etc/rc.d/rc.messagebus start
fi

# Start the session/seat daemon:
if [ -x /etc/rc.d/rc.elogind -a -x /bin/loginctl ]; then
  /etc/rc.d/rc.elogind start
elif [ -x /etc/rc.d/rc.consolekit -a -x /usr/sbin/console-kit-daemon ]; then
  /etc/rc.d/rc.consolekit start
fi

# Start Bluetooth:
if [ -x /etc/rc.d/rc.bluetooth ]; then
  /etc/rc.d/rc.bluetooth start
fi

# Start networking daemons:
if [ -x /etc/rc.d/rc.inet2 ]; then
  /etc/rc.d/rc.inet2
fi

# Mount any additional filesystem types that haven't already been mounted:
mount -a -v 2> /dev/null | grep -v -e "already mounted" -e "ignored" | cut -f 1 -d : | tr -d ' ' | while read dev ; do mount | grep "${dev} " ; done

# Start the Network Time Protocol daemon:
if [ -x /etc/rc.d/rc.ntpd ]; then
  /etc/rc.d/rc.ntpd start
fi

# Remove stale locks and junk files (must be done after mount -a!)
/bin/rm -f /var/lock/* /var/spool/uucp/LCK..* /tmp/.X*lock /tmp/.X11-unix/* 2> /dev/null

# Ensure basic filesystem permissions sanity.
chmod 755 / 2> /dev/null
chmod 1777 /tmp /var/tmp

# Start ACPI daemon.
if [ -x /etc/rc.d/rc.acpid ]; then
  /etc/rc.d/rc.acpid start
fi

# Enable CPU frequency scaling:
if [ -x /etc/rc.d/rc.cpufreq ]; then
  /etc/rc.d/rc.cpufreq start
fi

# Update any existing icon cache files:
if find /usr/share/icons -maxdepth 2 2> /dev/null | grep -q icon-theme.cache ; then
  for theme_dir in /usr/share/icons/* ; do
    if [ -r ${theme_dir}/icon-theme.cache ]; then
      echo "Updating icon-theme.cache in ${theme_dir}..."
      /usr/bin/gtk-update-icon-cache -t -f ${theme_dir} 1> /dev/null 2> /dev/null &
    fi
  done
  # This would be a large file and probably shouldn't be there.
  if [ -r /usr/share/icons/icon-theme.cache ]; then
    echo "Deleting icon-theme.cache in /usr/share/icons..."
    #/usr/bin/gtk-update-icon-cache -t -f /usr/share/icons 1> /dev/null 2> /dev/null &
    rm -f /usr/share/icons/icon-theme.cache
  fi
fi

# Update mime database:
if [ -x /usr/bin/update-mime-database -a -d /usr/share/mime ]; then
  echo "Updating MIME database:  /usr/bin/update-mime-database /usr/share/mime &"
  /usr/bin/update-mime-database /usr/share/mime 1> /dev/null 2> /dev/null &
fi

# These GTK+/pango files need to be kept up to date for
# proper input method, pixbuf loaders, and font support.
if [ -x /usr/bin/update-gtk-immodules ]; then
  echo "Updating gtk.immodules:"
  echo "  /usr/bin/update-gtk-immodules &"
  /usr/bin/update-gtk-immodules > /dev/null 2>&1 &
fi
if [ -x /usr/bin/update-gdk-pixbuf-loaders ]; then
  echo "Updating gdk-pixbuf.loaders:"
  echo "  /usr/bin/update-gdk-pixbuf-loaders &"
  HOME=/root /usr/bin/update-gdk-pixbuf-loaders > /dev/null 2>&1 &
fi
if [ -x /usr/bin/update-pango-querymodules ]; then
  echo "Updating pango.modules:"
  echo "  /usr/bin/update-pango-querymodules &"
  /usr/bin/update-pango-querymodules > /dev/null 2>&1 &
fi
if [ -x /usr/bin/glib-compile-schemas ]; then
  echo "Compiling GSettings XML schema files:"
  echo "  /usr/bin/glib-compile-schemas /usr/share/glib-2.0/schemas &"
  /usr/bin/glib-compile-schemas /usr/share/glib-2.0/schemas >/dev/null 2>&1 &
fi

# Start dnsmasq, a simple DHCP/DNS server:
if [ -x /etc/rc.d/rc.dnsmasq ]; then
  /etc/rc.d/rc.dnsmasq start
fi

# Start smartd, which monitors the status of S.M.A.R.T. compatible
# hard drives and reports any problems:
if [ -x /etc/rc.d/rc.smartd ]; then
  /etc/rc.d/rc.smartd start
fi

# Turn on process accounting.  To enable process accounting, make sure the
# option for BSD process accounting is enabled in your kernel, and then
# create the file /var/log/pacct (touch /var/log/pacct).  By default, process
# accounting is not enabled (since /var/log/pacct does not exist).  This is
# because the log file can get VERY large.
if [ -x /sbin/accton -a -r /var/log/pacct ]; then
  chmod 640 /var/log/pacct
  /sbin/accton /var/log/pacct
fi

# Start crond (Dillon's crond):
if [ -x /etc/rc.d/rc.crond ]; then
  /etc/rc.d/rc.crond start
fi

# Start atd (manages jobs scheduled with 'at'):
if [ -x /etc/rc.d/rc.atd ]; then
  /etc/rc.d/rc.atd start
fi

# Load a custom screen font if the user has an rc.font script.
if [ -x /etc/rc.d/rc.font ]; then
  /etc/rc.d/rc.font
fi

# Load a custom keymap if the user has an rc.keymap script.
if [ -x /etc/rc.d/rc.keymap ]; then
  /etc/rc.d/rc.keymap
fi

# Start the MariaDB database:
if [ -x /etc/rc.d/rc.mysqld ]; then
  /etc/rc.d/rc.mysqld start
fi

# Start the SASL authentication server.  This provides SASL
# authentication services for sendmail/postfix:
if [ -x /etc/rc.d/rc.saslauthd ]; then
  /etc/rc.d/rc.saslauthd start
fi

# Start OpenLDAP:
if [ -x /etc/rc.d/rc.openldap ]; then
  /etc/rc.d/rc.openldap start
fi

# Start WireGuard
if [ -x /etc/rc.d/rc.wireguard ]; then
  /etc/rc.d/rc.wireguard start
fi

# Start avahi:
if [ -x /etc/rc.d/rc.avahidaemon ]; then
  /etc/rc.d/rc.avahidaemon start
  /etc/rc.d/rc.avahidnsconfd start
fi

# Start Samba (a file/print server for Windows machines).
# Samba can be started in /etc/inetd.conf instead.
if [ -x /etc/rc.d/rc.samba ]; then
  /etc/rc.d/rc.samba start
fi

# Start mcelog
if [ -x /etc/rc.d/rc.mcelog ]; then
  /etc/rc.d/rc.mcelog start
fi

# If there are SystemV init scripts for this runlevel, run them.
if [ -x /etc/rc.d/rc.sysvinit ]; then
  /etc/rc.d/rc.sysvinit
fi

# Start the local setup procedure.
if [ -x /etc/rc.d/rc.local ]; then
  /etc/rc.d/rc.local
fi

# All done.
