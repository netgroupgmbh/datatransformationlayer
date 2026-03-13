#!/usr/bin/env bash
#
# =============================================================================
#title:         runtests.sh
#description:   Führt die Tests der Softwate aus
#author:        Patrick Froch <p.froch@netgroup.de>
#date:          20240801
#version:       1.1.0
#usage:         runtests.sh
# =============================================================================
#


## Ausgabe
function myecho() {
    if [ "${VERBOSE}" == "TRUE" ]
    then
        echo -e "\e[1;96m\n================================================================================"
        echo -e "${1}"
        echo -e "--------------------------------------------------------------------------------\n\e[0m"
    fi
}

function myinfo() {
    if [ "${VERBOSE}" == "TRUE" ]
    then
        echo -e "\e[0;37m\n================================================================================"
        echo -e "${1}"
        echo -e "--------------------------------------------------------------------------------\n\e[0m"
    fi
}

function myerror() {
    if [ "${VERBOSE}" == "TRUE" ]
    then
        echo -e "\n\e[1;91m================================================================================\e[0m"
        echo -e "\e[0;101m\u2717 ${1}\e[0m"
        echo -e "\e[1;91m--------------------------------------------------------------------------------\e[0m"
    else
        echo -e "\e[0;101m\u2717 ${1}\e[0m"
    fi
}

function myshortecho() {
    if [ "${VERBOSE}" != "TRUE" ]
    then
        echo -e "\e[0;92m\u2713 ${1}\e[0m"
    fi
}


## Variablen
error=0
tmperr=0
configFolder='./build'
toolFolder="${configFolder}/tools"
classesFolder='./Classes'
EXTENDED="TRUE"
PHP="php"
FIX=""
TESTDOX=""

if [ -f $HOME/bin/php ]
then
    PHP="$HOME/bin/php"
fi


##
# Parameters
##
while [ $# -gt 0 ]
do
    case ${1} in
    -v|--verbose)
        VERBOSE="TRUE"
        #shift  # Kein shift, da kein Wert übergeben wird!
        ;;

    -e|--extended)
        EXTENDED="TRUE" # Bis auf Weiteres immer true! Wenn nicht, Zeile 92 entfernen!
        #shift  # Kein shift, da kein Wert übergeben wird!
        ;;

    -f|--fix)
        FIX="--fix"
        #shift  # Kein shift, da kein Wert übergeben wird!
        ;;

    -t|--testdox)
        TESTDOX="--testdox"
        ;;

    *)          # unknown option
        myerror "Parameter [${1}] unbekannt!"
        #shift  # Kein shift, da kein Wert übergeben wird!
        ;;
    esac
    shift
done


##
# Header
#
echo -e "\e[1;96m\n================================================================================"
echo -e "NetGroup GmbH - Test Suite by Patrick Froch - Version: 1.0.1"
echo -e "--------------------------------------------------------------------------------"
echo -n "PHP-Version: "
$PHP -v | grep ^PHP | cut -d' ' -f2 | cut -d'-' -f1
echo -e "\n\e[0m"


## Easy Coding Standard
if [ -f ../../../vendor/bin/ecs ] && [ "TRUE" == "${EXTENDED}" ]
then
    myecho "Prüfe Code-Style mit Easy Coding Standard"
    ${PHP} ../../../vendor/bin/ecs ${FIX} --config=build/ecs.php
    tmperr=$?

    if [ ${tmperr} -ne 0 ]
    then
        error=${tmperr}
        myerror "Es ist ein Fehler ausgetreten [${tmperr}]"
    fi
else
    myinfo "Prüfen des Code-Style mit Easy Coding Standard ausgelassen. ecs nicht vorhanden!"
fi


## PHPStan
if [ -f ../../../vendor/bin/phpstan ] && [ "TRUE" == "${EXTENDED}" ]
then
    myecho "Prüfe Code-Qualität mit PHPStan"

    if [ "${VERBOSE}" == "TRUE" ]
    then
        ${PHP} ../../../vendor/bin/phpstan analyse -c "${configFolder}/phpstan.neon"
        tmperr=$?
    else
        ${PHP} ../../../vendor/bin/phpstan analyse -q -c "${configFolder}/phpstan.neon"
        tmperr=$?
    fi

    if [ ${tmperr} -ne 0 ]
    then
        error=${tmperr}
        ../../../vendor/bin/phpstan analyse -c "${configFolder}/phpstan.neon"
    else
       myshortecho "Prüfen der Code-Qualität mit PHPStan erfolgreich"
    fi
else
    myinfo "Prüfen der Code-Qualität mit PHPStan ausgelassen. PHPStan nicht vorhanden!"
fi


## PHPUnit
if [ -f ../../../vendor/bin/phpunit ] && [ -d ./Tests ]
then
    # PHPUnit gobal mit composer installiert
    echo
    myecho "Führe UnitTests mit globalem PHPUnit durch"
    XDEBUG_MODE=coverage ${PHP} -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_STRICT" ../../../vendor/bin/phpunit --configuration ${configFolder}/phpunit/phpunit.xml.dist $TESTDOX
    tmperr=$?

    if [ ${tmperr} -ne 0 ]
    then
        error=${tmperr}
        myerror "Es ist ein Fehler ausgetreten [${tmperr}]"
    fi
else
    myinfo "Ausführen der UnitTests ausgelassen. PHPUnit nicht vorhanden!"
fi

echo


## Zusammenfassung
if [ ${error} -ne 0 ]
then
    if [ "${VERBOSE}" != "TRUE" ]
    then
        echo
    fi

    myerror ">>>>>>>>>> Bei der Verarbeitung der Tests sind Fehler aufgetreten ! <<<<<<<<<<"
    echo
    exit 127
else

    myecho ">>>>>>>>>>>>>>>>>>>>>>> Es sind keine Fehler aufgetreten <<<<<<<<<<<<<<<<<<<<<<<"
    echo
    exit 0
fi



