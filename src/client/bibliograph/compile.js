function compile(data, callback) {
  const fs = require('fs');
  const path = require('upath');
  const process = require('process');
  
  // copy external resources
  let cwd = process.cwd();
  let src_path = path.join( cwd, '..', '..' );
  let vcslib_path = path.join( src_path, 'vcslib' );
  [
    { 
      script_name : 'raptor-client.js', 
      source_dir : path.join( vcslib_path, 'raptor-client'), 
      target_dir : path.join( cwd, "source", "resource", "js" )
    }
  ]
  .map( (resource )=>{ 
    fs.copyFileSync( 
      path.join( resource.source_dir, resource.script_name),
      path.join( resource.target_dir, resource.script_name)
    );
  });
	callback(null, data);
}