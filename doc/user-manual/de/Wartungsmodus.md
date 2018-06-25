Wartungsmodus
=============
::: {dir="ltr"}
Bei normalem Gebrauch und öffentlicher Zugänglichkeit einer Installation müssen unzulässige Veränderungen an der Datenbank verhindert werden. Deswegen sind bestimmte Vorgänge, wie etwa die Aktualisierung der Software, nur im Wartungsmodus möglich. 

Hierzu müssen Sie auf den Server zugreifen können, auf dem Bibliograph installiert ist. Wechseln Sie ins Unterverzeichnis "services/config", laden Sie die Datei "server.conf.php" in einen Editor und suchen Sie die Zeile mit dem Eintrag

    define( "QCL_APPLICATION_STATE", "production" );

Ändern Sie die Zeile in

    define( "QCL_APPLICATION_STATE", "maintenance" );

und speichern Sie die Datei ab.

Dann können Sie den Vorgang durchführen. Vergessen Sie nicht, nach Ende der Wartungsvorgangs die Zeile wieder in 

[    ]{style="font-size:13.3333330154419px;line-height:16.6666660308838px"}[define( "QCL_APPLICATION_STATE", "production" );]{style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
[
]{style="font-size:13.3333330154419px;line-height:16.6666660308838px"}
[zu verändern und die Konfigurationsdatei erneut abzuspeichern. Bibliograph zeigt den Wartungsmodus oben in der Menüleiste an. ]{style="font-size:13.3333330154419px;line-height:16.6666660308838px"}


:::
