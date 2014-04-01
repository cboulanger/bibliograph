PLEASE NOTE:

You need to copy over the plugin folder from this folder into the 
resource/bibliograph/plugin folder, with a generator job such as:

      // copy plugin development files
      "copy-files" :
      {
        "files"     : [ 
          "plugin1", 
          "plugin2" 
        ],
        "source" : "source/class/bibliograph/plugin",
        "target" : "source/resource/bibliograph/plugin"
      }
      
otherwise the files will not be included in the build version and
cannot be loaded by the client. 