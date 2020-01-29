#
# function library for scripts in tool/ dir
#

source ./.env

# yii shorthand function
function yii() {
  tool/dev/yii "$@"
}
export -f yii

# yii-test shorthand function (console command using a dedicated config file for tests)
function yii_test() {
  tool/dev/yii-test "$@"
}
export -f yii_test

# codecept shorthand function
function codecept() {
  tool/dev/codecept "$@"
}
export -f codecept

# mysql root user access shorthand function
function mysql_root() {
  tool/dev/mysql-client -uroot -p$DB_ROOT_PASSWORD "$@"
}
export -f mysql_root

# mysql normal user access shorthand function
function mysql_user() {
  tool/dev/mysql-client -u$DB_USER -p$DB_PASSWORD "$@"
}
export -f mysql_user

# Colorize output
# shellcheck disable=SC2155
export FONT_BOLD=$(tput bold)
export COLOR_RED=$(tput setaf 1)
export COLOR_GREEN=$(tput setaf 2)
export COLOR_BLUE=$(tput setaf 4)
export COLOR_GREY=$(tput setaf 8)
export STYLE_RESET=$(tput sgr0)

function log_heading {
  echo $FONT_BOLD$COLOR_BLUE
  echo ==============================================================================
  echo $1
  echo ==============================================================================
  echo $STYLE_RESET
}
export -f log_heading

function log_debug {
  echo $COLOR_COLOR_GREY$1
}
export -f log_debug

function log_info {
  echo $1
}
export -f log_info

function log_warn {
  >&2 echo $COLOR_RED$1
  echo $STYLE_RESET
}
export -f log_warn

function log_error {
  >&2 echo $FONT_BOLD$COLOR_RED$1
  echo $STYLE_RESET
}
export -f log_error
