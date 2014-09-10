#!/bin/bash
echo "This script creates a skeleton for a Bibliograph plugin."
echo
namespace=""
while [ -z $namespace ]; do
    echo -n "Please enter the namespace of the new plugin: "
    read namespace
    if [ -d plugins/$namespace ]; then
        echo
        echo "This namespace already exists. Please use another one."
        namespace=""
    fi
    if ! [[ $namespace =~ ^[a-zA-Z0-9]+$ ]]; then
        echo
        echo "Invalid input. Only ASCII characters and numbers are allowed."
        namespace=""
fi
done
echo ">>> Creating plugin '$namespace'..."

# copy skeleton
mkdir plugins/$namespace
cp -a plugins/template/* plugins/$namespace/

# rename directories
mv plugins/$namespace/services/class/template \
    plugins/$namespace/services/class/$namespace 
mv plugins/$namespace/source/class/template \
    plugins/$namespace/source/class/$namespace 
mv plugins/$namespace/source/resource/template \
    plugins/$namespace/source/resource/$namespace 
    
# replace "template" with namespace in files

find ./plugins/$namespace -type f -regex ".*/.*\.\(json\|js\|php\)" \
    -exec sed -i'' -e "s/template/$namespace/g" '{}' +
    
echo ">>> Skeleton for '$namespace'-Plugin has been created."
echo "    If your plugin has a javascript frontend, please add the following code:"
echo
echo "    config.json/jobs/parts-config/packages/parts:"
echo "    \"plugin_$namespace\"  : { \"include\" : [ \"$namespace.Plugin\" ] }"
echo 
echo "    source/class/bibliograph/PluginManager.js:after 'var plugins= {':"
echo "    \"plugin_$namespace\"  : window.$namespace ? $namespace.Plugin : null"
echo 
echo "    Otherwise, you can delete the 'source' folder."
echo
echo "    To activate the plugin and make it visible to the plugin manager,"
echo "    change the \$visible property to true in"
echo "    plugins/$namespace/services/class/$namespace/Plugin.php"
echo

