#!/bin/sh
. /etc/alternc/local.sh

DATA_PART=`df ${ALTERNC_LOC} 2>/dev/null | awk '/^\// { print $1 }'`

# quota will give over NFS will print the partition using the full NFS name
# (e.g. 10.0.0.1:/var/alternc) so we need to lookup first with mount
# to convert DATA_PART if needed.
QUOTA_PART=`mount | sed -n -e "s,\([^ ]*\) on ${DATA_PART} type nfs.*,\1,p"`
if [ -z "$QUOTA_PART" ]; then
    QUOTA_PART="$DATA_PART"
fi

# quota will split its display on two lines if QUOTA_PART is bigger than 15
# characters. *sigh*
PART_LEN=`echo -n "$QUOTA_PART" | wc -c`
if [ "$PART_LEN" -gt 15 ]; then
    quota -g "$1" |
       sed -n -e "\\;${QUOTA_PART}w,+1s/ *\([0-9]*\).*/\1/p"
    quota -g "$1" |
       sed -n -e "\\;${QUOTA_PART}w,+1s/ *[0-9]* *\([0-9]*\).*/\1/p"
else
    quota -g "$1" | awk /${QUOTA_PART//\//\\\/}/\ {print\ '$2'}
    quota -g "$1" | awk /${QUOTA_PART//\//\\\/}/\ {print\ '$3'}
fi

