Maintenance mode
================
::: {dir="ltr"}
[When the Bibliograph installation is in production mode, i.e. when it is publically accessible, it is important to prohibit unauthorized modifications of the database. Therefore, certain procedures, such as the upgrade of the software, can only take place when the software is in maintenance mode.]{style="font-size:13.3333330154419px;line-height:16.6666660308838px"}

In order to switch to maintenance mode, you need access to the server and to the directory in which Bibliograph is installed. Change to the subdirectory[ "services/config", load the file "server.conf.php" into an editor and look for the line containing]{style="font-size:13.3333330154419px;line-height:16.6666660308838px;background-color:transparent"}
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
:::
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
    define( "QCL_APPLICATION_STATE", "production" );
:::
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
:::
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
Change this line into
:::
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
:::
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
    define( "QCL_APPLICATION_STATE", "maintenance" );
:::
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
:::
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
and save the file.
:::
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
:::
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
After that, the intended action should be possible. Do not forget to change the line back to
:::
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
:::
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
[    ]{style="font-size:13.3333330154419px;line-height:16.6666660308838px"}[define( "QCL_APPLICATION_STATE", "production" );]{style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
:::
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
[
]{style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
:::
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
[and save the configuration file afterwards. Bibliograph will indicate maintenance mode in the menu bar on the top of the user interface. ]{style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
:::
::: {style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
:::
:::
