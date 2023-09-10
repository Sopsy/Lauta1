#!/bin/sh
set -e
set -u

TOKEN=${1}
FILE=${2}
DEST=${3}
TEMP=/tmp/ip2location

error() {
  echo "[ERROR] ${1}";
  exit 0
}

# Verify required commands exist
for a in wget unzip wc find grep; do
  if [ -z "$(which ${a})" ]; then
    error "Command \"${a}\" not found"
  fi
done

# Create temp dir
if [ ! -d ${TEMP} ]; then
  mkdir ${TEMP}

  if [ ! -d ${TEMP} ]; then
    error "Failed to create ${TEMP}"
  fi
fi

# Download DB
curl --fail --silent --show-error -o ${TEMP}/database.zip "https://www.ip2location.com/download?token=${TOKEN}&file=${FILE}"

# Verify download
if [ ! -f ${TEMP}/database.zip ]; then
    error "Download failed"
fi

if [ ! -z "$(grep -i 'NO PERMISSION' ${TEMP}/database.zip)" ]; then
    error "IP2Location responded: $(cat ${TEMP}/database.zip)"
fi

if [ ! -z "$(grep -i '5 TIMES' ${TEMP}/database.zip)" ]; then
  error "IP2Location responded: $(cat ${TEMP}/database.zip)"
fi

if [ $(wc -c < ${TEMP}/database.zip) -lt 102400 ]; then
  error "Download failed, database smaller than 100kb"
fi

# Decompress database
unzip -q -o -d ${TEMP} ${TEMP}/database.zip

FILENAME=$(find ${TEMP} -name '*.BIN')
# Verify a .bin file exists
if [ -z ${FILENAME} ]; then
    error "*.BIN not found in downloaded zip file"
fi

# Move it to destination and set permissions
mv -f ${FILENAME} ${DEST}
chmod 644 ${DEST}

# Remove temp folder
rm -rf /tmp/ip2location