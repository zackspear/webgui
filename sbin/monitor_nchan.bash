#!/bin/bash
docroot=/usr/local/emhttp                            # webGui root folder
nchan_pid=/var/run/nchan.pid                         # keeps list of nchan processes registered by GUI
disk_load=/var/local/emhttp/diskload.ini             # disk load statistics
nginx=/var/run/nginx.socket                          # nginx local access
status=http://localhost/pub/session?buffer_length=1  # nchan information about GUI subscribers
nchan_list=/tmp/nchan_list.tmp
nchan_id=$(basename "$0")

nchan_stop() {
  echo -n >$nchan_list
  pid=$(cat $pid_file)
  while IFS=$'\n' read -r nchan; do
    [[ ${nchan##*/} == '.*' ]] && continue
    echo $nchan >>$nchan_list
    pkill --ns $pid -f $nchan
  done <<< $(ps -eo cmd | grep -Po '/usr/local/emhttp/.*/nchan/.*')
}

nchan_start() {
  [[ -e $nchan_list ]] || return
  pid=$(cat $pid_file)
  while IFS=$'\n' read -r nchan; do
    if ! pgrep --ns $pid -f $nchan >/dev/null; then
      $nchan &>/dev/null &
    fi
  done < $nchan_list
  rm -f $nchan_list
}

if [[ $1 == kill ]]; then
  echo "Stopping nchan processes..."
  nchan_stop
  rm -f $nchan_pid $disk_load
  exit
fi

if [[ $1 == stop ]]; then
  echo "Stopping nchan processes..."
  nchan_stop
  exit
fi

if [[ $1 == start ]]; then
  echo "Starting nchan processes..."
  nchan_start
  exit
fi
