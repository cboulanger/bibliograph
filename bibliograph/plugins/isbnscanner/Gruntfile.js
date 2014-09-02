// global conf
var common = {
  QOOXDOO_VERSION: '3.5.1',
  QOOXDOO_PATH: '../../../../qooxdoo-3.5.1-sdk'
};

// requires
var qxConf = require(common.QOOXDOO_PATH + '/tool/grunt/config/application.js');
var qxTasks = require(common.QOOXDOO_PATH + '/tool/grunt/tasks/tasks.js');

// grunt
module.exports = function(grunt) {
  var config = {

    generator_config: {
      let: {
      }
    },

    common: common,

    /*
    myTask: {
      options: {},
      myTarget: {
        options: {}
      }
    }
    */
  };

  var mergedConf = qxConf.mergeConfig(config);
  grunt.initConfig(mergedConf);

  qxTasks.registerTasks(grunt);

  // grunt.loadNpmTasks('grunt-my-plugin');
};
