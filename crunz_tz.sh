#! /bin/sh

# usage:
# crunz_tz.sh /path/to/crunz.yml

#TZ should have a default in Dockerfile and (optionally) be set at container runtime
TZ="${TZ:-Etc/GMT}"

# replace "timezone: SOMETHING" in crunz.yml with TZ env
sed -i -e "s!timezone: [^\n\r]*!timezone: $TZ!" $1

echo "Updated crunz with TZ $TZ"