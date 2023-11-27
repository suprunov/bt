#!/bin/bash
siteDir="${HOME}/www/blacktyres.ru"
maxKeepDays=92  # 3-4 months
maxLifeDays=276 # 9-10 months

# 1) log folder
logDir="${siteDir}/logs"
if [ -d "${logDir}" ]; then
  # archive logs
  for file in $(find "${logDir}" -type f -name "*.log" -mtime +${maxKeepDays}); do
    # tar -czf ${file}.tar.gz $file
    gzip "$file"
  done
  # remove old archives
  for file in $(find "${logDir}" -type f -name "*.gz" -mtime +${maxLifeDays}); do
    rm "$file"
  done
fi

# 2) import_files folder
importDir="${siteDir}/import_files"
logDirs=("${importDir}/orders_log" "${importDir}/profile_1c_log" "${importDir}/seasonalcontract_log" "${importDir}/profile_site")
for logDir in ${logDirs[*]}; do
  if [ -d "${logDir}" ]; then
    # archive logs
    for file in $(find "${logDir}" -type f -name "*.xml*" -mtime +${maxKeepDays} | grep -v "\.gz$"); do
      # tar -czf ${file}.tar.gz $file
      gzip "$file"
    done
    # remove old archives
    for file in $(find "${logDir}" -type f -name "*.gz" -mtime +${maxLifeDays}); do
      rm "$file"
    done
  fi
done
