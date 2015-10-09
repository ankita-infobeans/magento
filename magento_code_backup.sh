#!/bin/bash

################################################################################
# FUNCTIONS
################################################################################

# 1. Check required system tools
_check_installed_tools() {
    local missed=""

    until [ -z "$1" ]; do
        type -t $1 >/dev/null 2>/dev/null
        if (( $? != 0 )); then
            missed="$missed $1"
        fi
        shift
    done

    echo $missed
}

# 2. Selftest for checking tools which will used
checkTools() {
    REQUIRED_UTILS='nice sed tar head gzip getopt'
    MISSED_REQUIRED_TOOLS=`_check_installed_tools $REQUIRED_UTILS`
    if (( `echo $MISSED_REQUIRED_TOOLS | wc -w` > 0 ));
    then
        echo -e "Unable to create backup due to missing required bash tools: $MISSED_REQUIRED_TOOLS"
        exit 1
    fi
}

# 3. Create code dump function
createCodeDump() {
    cd $MAGE_FOLDER

    # Content of file archive
    DISTR="
    app
    downloader
    errors
    includes
    js
    lib
    phplive
    scripts			
    pkginfo
    shell
    skin
    wsdl
    .htaccess
    *.ini
    *.php
    *.txt
    *.html
    mage
    *.patch
    *.sh
    #var/log/system.log
    #var/log/exception.log
    #var/log/shipping*.log
    #var/log/payment*.log
    #var/log/paypal*.log"

    # Create code dump
    DISTRNAMES=
    for ARCHPART in $DISTR; do
        if [ -r "$MAGENTOROOT$ARCHPART" ]; then
            DISTRNAMES="$DISTRNAMES $MAGENTOROOT$ARCHPART"
        fi
    done
    if [ -n "$DISTRNAMES" ]; then
        echo nice -n 15 tar -czhf $CODEFILENAME $DISTRNAMES
        nice -n 15 tar -czhf $CODEFILENAME $DISTRNAMES
    fi

    echo "DONE"
    date
}

################################################################################
# CODE
################################################################################
date
# Selftest
checkTools

# Magento folder
MAGE_FOLDER=/var/www/html/
MAGENTOROOT=

# Output path
OUTPUTPATH=/home/vgade/

#if not found then create
mkdir -p $OUTPUTPATH

# Input parameters
MODE=code
NAME=$(date +'%Y-%m-%d_%H-%M')

OPTS=`getopt -o m:n:o: -l mode:,name:,outputpath: -- "$@"`

if [ $? != 0 ]
then
    exit 1
fi

eval set -- "$OPTS"

while true ; do
    case "$1" in
        -m|--mode) MODE=$2; shift 2;;
        -n|--name) NAME=$2; shift 2;;
        -o|--outputpath) OUTPUTPATH=$2; shift 2;;
        --) shift; break;;
    esac
done

if [ -n "$NAME" ]; then
    CODEFILENAME="$OUTPUTPATH$NAME.tar.gz"
    DBFILENAME="$OUTPUTPATH$NAME.gz"
else
    # Get random file name - some secret link for downloading from magento instance :)
    MD5=`echo \`date\` $RANDOM | md5sum | cut -d ' ' -f 1`
    DATETIME=`date -u +"%Y%m%d%H%M"`
    CODEFILENAME="$OUTPUTPATH$MD5.$DATETIME.tar.gz"
    DBFILENAME="$OUTPUTPATH$MD5.$DATETIME.gz"
fi

if [ -n "$MODE" ]; then
    case $MODE in
        code) createCodeDump; exit 0;;
        check) exit 0;;
        *) echo Invalid mode; exit 1;;
    esac
fi

createCodeDump
date
exit 0
