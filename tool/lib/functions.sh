#
# function library for scripts in tool/ dir
#

if [[ "$TERM" == "" ]]; then
  export TERM=xterm-256color
fi

# Colorize output
# shellcheck disable=SC2155
export FONT_BOLD=$(tput -T $TERM bold)
export COLOR_RED=$(tput -T $TERM setaf 1)
export COLOR_GREEN=$(tput -T $TERM setaf 2)
export COLOR_BLUE=$(tput -T $TERM setaf 4)
export COLOR_GREY=$(tput -T $TERM setaf 8)
export STYLE_RESET=$(tput -T $TERM sgr0)
export CHECKMARK="${COLOR_GREEN}✔${STYLE_RESET}"
export CROSSMARK="${COLOR_RED}x${STYLE_RESET}"

function log_heading {
  echo $FONT_BOLD$COLOR_BLUE
  echo ==============================================================================
  echo $1
  echo ==============================================================================
  echo $STYLE_RESET
}
export -f log_heading

function log_debug {
  echo "${COLOR_GREY}$1${STYLE_RESET}"
}
export -f log_debug

function log_info {
  echo "$@"
}
export -f log_info

function log_warn {
  >&2 echo "${COLOR_RED}$1${STYLE_RESET}"
}
export -f log_warn

function log_error {
  >&2 echo "${FONT_BOLD}${COLOR_RED}$1${STYLE_RESET}"
}
export -f log_error

function exit_with_error {
  >&2 echo "${FONT_BOLD}${COLOR_RED}$1${STYLE_RESET}"
  exit 1
}
export -f log_error


# php shorthand function
function php() {
  tool/bin/php "$@"
}
export -f php

# yii shorthand function
function yii() {
  tool/bin/yii "$@"
}
export -f yii

# yii-test shorthand function (console command using a dedicated config file for tests)
function yii_test() {
  tool/test/yii "$@"
}
export -f yii_test

# codecept shorthand function
function codecept() {
  tool/bin/codecept "$@"
}
export -f codecept

# MySQL access

# mysql normal user access shorthand function
function mysql_user() {
  tool/dev/mysql-client -u$DB_USER -p$DB_PASSWORD "$@"
}
export -f mysql_user

# mysql root user access shorthand function
function mysql_root() {
  if [ "$DB_ROOT_PASSWORD" == "" ]; then
    log_error "You need to set the DB_ROOT_PASSWORD environment variable in the .env file (Do this only on a local development machine!)."
    exit 1
  fi
  tool/dev/mysql-client -uroot -p$DB_ROOT_PASSWORD "$@"
}
export -f mysql_root

function hr() {
  printf '%*s\n' "${COLUMNS:-$(tput -T $TERM cols)}" '' | tr ' ' -
}
export hr
export FUNCTIONS_LOADED=1
