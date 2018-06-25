Zugangskontrollwerkzeug
=======================
::: {dir="ltr"}



Das Zugangskontrollwerkzeug basiert auf dem Prinzip von Nutzern, Rollen, Gruppen, Datenbanken und Erlaubnissen und bietet die Möglichkeit, all diese Bereiche individuell miteinander zu verknüpfen.
::: {style="display:block;text-align:left"}
![](../_/rsrc/1409518456461/administration/access-control/zugangskontrolle.png)
:::

**Nutzer** (user)
sind hierbei die realen Personen, die auf die Anwendung zugreifen.
**Erlaubnisse** (permissions)
befähigen den Nutzer zum Zugriff auf bestimmte Funktionen von Bibliograph. Erlaubnisse können einem Nutzer einzeln hinzugefügt werden. Empfohlen wird jedoch stattdessen das Zuweisen von Rollen.
**Rollen** (roles)
sind vorgefertigte Sammlungen von Erlaubnissen. Bibliograph bietet vier verschiedene Rollen an:
Die *Administratorenrolle* befähigt den Nutzer zum Zugriff auf alle Funktionen (durch die "*"-Erlaubnis). Die *Managerrolle* beinhaltet einige Funktionen zur Verwaltung des Programms. Der *normale Nutzer* kann Datensätze ändern und bearbeiten. Die Erlaubnisse des *anonymen Nutzers* beschränken sich auf das Einsehen ausgewählter Datensätze.
Wenn keine der existenten Rollen die Anforderungen erfüllt, kann eine neue erstellt werden.
*Hinweis: Alle Rollen existieren getrennt voneinander. Das bedeutet, dass beispielsweise die Managerrolle nicht automatisch die Funktionen des normalen Nutzers enthält. Daher müssen beide Rollen zugewiesen werden, da der Manager sonst keinen Zugriff auf die grundlegensten Funktionen hat*.
**Gruppen** (groups)
sind Sammlungen von Nutzern, z.B. eine Forschungsgruppe. Ein Nutzer kann, abhängig von seiner Gruppenzugehörigkeit, verschieden Rollen haben. So kann es vorkommen, dass er auf eine Datenbank, die von seiner Gruppe verwaltet wird, zugreifen kann, während ihm der Zugang zu anderen Datenbank verweigert wird. Um eine Gruppe zu erzeugen, muss die Gruppe mit einer oder mehreren Datenbanken und den verschiedenen Nutzern, die ihr angehören, verknüpft werden.
**Datenbanken** (database)
sind Sammlungen bibliographischer Daten. Sie können den Nutzern auf verschiedene Arten zugänglich gemacht werden. Datenbanken können mit einzelnen Rollen, bestimmten Gruppen oder direkt mit einem Nutzer (persönliche Datenbank) verknüpft werden. [Um auf das Zugangskontrollwerkzeug zuzugreifen, wählen Sie das Funktionsfeld 'System' in der Werkzeugleiste und dort die Option ]{style="line-height:1.25;font-size:10pt;background-color:transparent"}*[Zugangskontrolle]{style="color:rgb(102,102,102)"}*[. Das Zugangskontrollwerkzeug öffnet sich in einem neuen Fenster. ]{style="line-height:1.25;font-size:10pt;background-color:transparent"}[Über das Wählfeld auf der linken Seite kann nun auf die Menüpunkte users, permissions, roles, groups und databases zugegriffen werden. ]{style="line-height:1.25;font-size:10pt;background-color:transparent"}[Nun erscheinen in dem linken der drei Fenster alle jeweis existierenden Rollen, Nutzer etc. ]{style="line-height:1.25;font-size:10pt;background-color:transparent"}[Wählt man nun eine der angezeigten Möglichkeiten aus, beispielsweise einen der Nutzer, werden im mittleren Feld unter dem Titel Relations alle aktuellen Verknüpfungen dieses Nutzers angezeigt. Durch einen weiteren Klick auf eine dieser Zuweisungen (beim Beispiel des Nutzers besteht die Wahl zwischen Datenbanken, Gruppen und Rollen) öffnen sich im rechten Fenster alle verknüpfbaren Elemente aus der gewählten Rubrik.]{style="font-size:10pt;line-height:1.25;background-color:transparent"}


Hinweise: 
-   [Verknüpfungen können durch die **Schaltflächen [*verknüpfen* ]{style="color:rgb(102,102,102)"}und [*lösen*]{style="color:rgb(102,102,102)"}** geändert werden.]{style="line-height:1.25;font-size:10pt;background-color:transparent"} [Die Plus- und Minus-Schaltflächen unter dem linken und dem rechten Fenster dienen zum löschen und hinzufügen neuer Erlaubnisse, Rollen und Gruppen. ]{style="line-height:1.25;font-size:10pt;background-color:transparent"}
-   [[Hinweis: Um eine Datenbank **für nicht registrierte Nutzer** zu öffnen, muss sie mit der Rolle des anonymen Nutzers verknüpft werden.
    ]{style="font-size:13.3333330154419px;line-height:16.6666660308838px"}]{style="line-height:1.25;font-size:10pt;background-color:transparent"}
-   [Zum  Hinzufügen von [**neuen** ](https://sites.google.com/a/bibliograph.org/docs-v2-de/administration/goog_1762510373)]{style="line-height:1.25;font-size:10pt;background-color:transparent"}**[Datenbanken](new-database.html)** [(erfordert den [Wartungsmodus](maintenance.html)!) ]{style="line-height:1.25;font-size:10pt;background-color:transparent"}[siehe ]{style="line-height:1.25;font-size:10pt;background-color:transparent"}[hier](new-database.html)[, für ]{style="line-height:1.25;font-size:10pt;background-color:transparent"}[**neue Nutzer** [hier]{style="line-height:1.25;font-size:10pt"}](creating-a-new-user.html)**. **
Zusätzlich zu den beschriebenen Funktionen verfügt das Zugangskontrollwerkzeug über den Button I[*ns Dateisystem exportieren*]{style="color:rgb(102,102,102)"}. Diese Funktion löscht alle anonymen Nutzerdaten und exportiert die Zugangskontrolldaten in den Backup-Ordner. Die Option darf nur im Wartungsmodus genutzt werden.
:::
