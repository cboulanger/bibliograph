// global conf
var common = {
  QOOXDOO_VERSION: '4.0.1',
  QOOXDOO_PATH: '../qooxdoo'
};

// requires
//var qxConf = require(common.QOOXDOO_PATH + '/tool/grunt/config/application.js');
//var qxTasks = require(common.QOOXDOO_PATH + '/tool/grunt/tasks/tasks.js');

// grunt
module.exports = function(grunt) {
  var config = {
    
    clean: {
      phpdocumentor: '<%= phpdocumentor.dist.target %>'
    },

    generator_config: {
      let: {
      }
    },

    common: common,

    phpdocumentor: {
      dist: {
        options : {
          directory : './services/class',
          target : 'services/api'
        }
      }
    }
  };

  //config = qxConf.mergeConfig(config);
  grunt.initConfig(config);
  grunt.loadNpmTasks('grunt-phpdocumentor');
  
  grunt.registerTask('api-php', [  'phpdocumentor' ]);
  
  //qxTasks.registerTasks(grunt);
};