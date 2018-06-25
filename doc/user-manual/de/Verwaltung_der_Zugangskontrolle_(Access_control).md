Verwaltung der Zugangskontrolle (Access control)
================================================
::: {dir="ltr"}
Bibliograph verfügt über eine flexibles und fein einstellbares System der Zugangskontrolle. Sie können genau definieren, welche Nutzer zu welchen Gruppen gehören und welche Rollen und Berechtigungen diese Nutzer innerhalb der Gruppen haben. Zusammen mit der optionalen Authentisierung über externe LDAP-Server steht Ihnen so ein System zur Verfügung, mit dem Sie die kollaborative Bearbeitung und die Veröffentlichung Ihrer Literaturbestände genau nach Ihren Vorstellungen gestalten können.
Das Werkzeug zur Verwaltung der Zugangskontrolldaten kann über den Menüpunkt System > Zugangskontrolle erreicht werden:
::: {style="display:block;text-align:left"}
[![](../_/rsrc/1409169553065/administration/verwaltung-der-zugangskontrolle/Bild%204.png)](verwaltung-der-zugangskontrolle/Bild%204.png%3Fattredirects=0)
:::
Es besteht aus drei Spalten. In der ersten Spalte befindet sich oben eine Auswahliste, welche die verschiedenen Objektarten der Zugangskontrolle enthält:
-   Benutzer/innen
-   Rollen
-   Gruppen
-   Erlaubnisse
-   Datenquellen
In der darunterliegenden Liste erscheinen die zugehörigen existierenden Objekte. In der zweiten Spalte werden die Verbindungen des in der Liste ausgewählten Objekts mit den anderen Objektarten angezeigt, d.h. z.B.
-   zu welcher Gruppe gehört ein Benutzerobjekt,
-   welche Erlaubnisse umfasst eine Rolle,
-   welche Datenbank ist eine Gruppe zugeordnet,
-   welche Rolle hat ein Benutzerobjekt innerhalb einer bestimmten Gruppe. 
Die angezeigte Baumstruktur kann bearbeitet werden, d.h. es können Verbindungen hinzugefügt oder gelöst werden. Die dritte Spalte zeigt Objekte an, die mit der in der mittleren Spalte ausgewählten Objektart verbunden werden können.
An dieser Stelle noch  einige Erläuterungen zu den Objektarten:
#### []{#TOC-Benutzer-innen-und-Gruppen}Benutzer/innen und Gruppen 
Die Benutzerobjekte stellen jeweils eine/n angemeldete/n und authentisierten Benutzer dar. Die Benutzerobjekte werden entweder vom Administrator erstellt oder bei der Anmeldung über einen LDAP-Server automatisch angelegt. Gruppen fassen Benutzer zusammen.
-   Es ist möglich, einzelnen Benutzer/innen Zugang zu einer eigenen Datenbank zu geben, die damit zu deren privaten Datenbank wird. Üblicherweise aber werden Benutzerobjekten bestimmten Gruppen zugeordnet, die wiederum Zugriff auf Datenbanken haben.
-   Benutzerobjekte müssen einer Rolle zugeordnet werden, die dem Benutzerobjekt Erlaubnisse verleiht. Generell haben Benutzerobjekte ihre Rollen nur innerhalb einer bestimmten Gruppe. Es ist aber auch möglich, Benutzerobjekten sogenannte *globale* Rollen zu geben, die in allen Gruppen gelten
-   Falls Bibliograph so konfiguriert ist, dass die Authentisierung der Nutzer/innen über einen LDAP-Server geschieht, werden Gruppen und Benutzer von diesem verwaltet. Nutzer- und Gruppendaten werden, soweit nötig, in das Programm importiert, können aber nicht bearbeitet werden. So besteht z.B. keine Möglichkeit, das Passwort zu ändern. Wenden Sie sich in diesem Fall an den Administrator des LDAP-Servers.
#### []{#TOC-Rollen-und-Erlaubnisse}Rollen und Erlaubnisse 
Eine Erlaubnis stellt die Berechtigung dar, eine bestimmte Aktion auszuführen oder auf bestimmte Informationen zugreifen zu können. So wird etwa die Berechtigung, einen Datensatz zu bearbeiten, durch die Erlaubnis "reference.edit" dargestellt. Diese wird zum Beispiel der Rolle "Angemeldeter Benutzer" zugeordnet. Sie könnte aber auch nur dem "Manager" zugeordnet werden. So wie Gruppen Nutzer bündeln, so fassen Rollen Erlaubnisse zusammen. Benutzerobjekte haben nie direkt Erlaubnisse, sie bekommen diese immer nur über die Rollen, denen sie angehören.
-   Es ist meistens, aber nicht immer sinnvoll, dass jede Erlaubnis genau einer Rolle angehört, und Benutzerobjekte dann mehrere Rollen mit unterschiedlichen Erlaubnissen bekommen.
-   Anders ist es z.B. im Fall der Rolle "Anonym", die seperat bestimmte Erlaubnisse erhält, die auch anderen Rollen zugeteilt werde. Benutzerobjekte sind *entweder* "Anonym" *oder* haben eine oder mehrere andere Rollen. Für jeden nicht im System angemeldeten Nutzer wird ein temporäres Benutzerobjekt mit der Rolle "Anonym" angelegt.
-   Rollen können einem Benutzerobjekt direkt zugeordnet werden (globale Rollen), sie haben dann diese Rollen in allen Gruppen. Sie können aber aber einem Benutzerobjekt eine Rolle nur in einer bestimmten Gruppe zuteilen, und damit etwa für die der Gruppe zugeordneten Datenbanken.
[]{#TOC-H-ufig-gebrauchte-Verwaltungsfunktionen}Häufig gebrauchte Verwaltungsfunktionen
---------------------------------------------------------------------------------------
Das Zugangskontrollwerkzeug erlaubt es, das Zugangskontrollsystem sehr flexibel zu bearbeiten. Eine detaillierte Beschreibung aller Funktionen würde den Rahmen dieser Kurzanleitung sprengen, aber nicht alle Funktionen werden in der Praxis auch gebraucht. Ich beschränke mich daher auf die wichtigsten Funktionen, die der oder die Administrator/in normalerweise braucht.
### []{#TOC-Benutzerobjekte-anlegen}Benutzerobjekte anlegen
Wenn kein externer LDAP-Server für die Nutzerverwaltung gebraucht wird, werde alle Benutzer/innen manuell angelegt. Eine Möglichkeit für die Nutzer, sich selbst anzumelden, besteht zur Zeit noch nicht (Bei Bedarf kann hierfür ein Plugin entwickelt werden). Um ein neues Benutzerobjekt anzulegen, gehen Sie so vor:
-   Im Drop-Down-Menu oben in der linken Spalte wählen sie "Benutzer/innen" aus.
-   Danach wird eine Liste der bereits registrierten User angezeigt.
-   Klicken Sie auf das Plus-Zeichen (+) unten links. Danach werden Sie aufgefordert, die Benutzerkennung einzugeben. Die Benutzerkennung ist der "Login"-Name und darf nur aus Buchstaben, Zahlen und dem Unterstrich bestehen. Es sind keine Umlaute, Leer- oder Sonderzeichen erlaubt. 
-   Nach der Eingabe wird das neue Benutzerobjekt in der Liste angezeigt.
-   Um die Eigenschaften des Benutzerobjekts zu bearbeiten, klicken Sie auf den neuen Eintrag und dann auf die Schaltfläche mit dem Stift ("Bearbeiten").
-   Im dem dann angezeigten Dialog geben Sie bitte den vollständigen Namen des Benutzers oder der Benutzerin sowie deren E-Mailaddresse ein.  Sie können ein Passwort eingeben, üblicherweise aber lassen Sie das Passwortfeld leer. Es dann wird ein temporäres Passwort erzeugt.
-   Das Programm schickt dann an die angegebene E-Mailadresse eine Nachricht mit dem Benutzernamen, dem Passwort und einem Link, mit dem der/die Empfänger/in die E-Mailadresse bestätigt.
<!-- -->
-   Falls Sie nicht mit Gruppen arbeiten, oder der oder die Benutzerin in allen Gruppen die selbe Rolle übernehmen soll, können Sie dem Objekt nun eine Rolle zuordnen, indem Sie in der mittleren Spalte "Rollen > In allen Gruppen" und in der rechten Spalte die gewünsche Rolle anwählen und dann auf "Verknüpfen" klicken. Im Normalfall wird einem neues Nutzerobjekt die Rolle "Normal User" zugewiesen.
### []{#TOC-Datenbanken-verwalten}Datenbanken verwalten
Es gibt unterschiedliche Arten von Datenbanken (oder auch: "Datenquellen") im Bibliograph, aber normalerweise arbeiten Sie nur mit den bibliographischen Datenbanken. Bei der Installation des Programmes werden zwei davon angelegt, "Database 1" und "Database 2". Sie können als Administrator die Datenbanken editieren, wenn Sie im Drop-Down links oben die Auswahl "Datenquellen" wählen:
::: {style="display:block;text-align:left"}
[![](../_/rsrc/1409169553065/administration/verwaltung-der-zugangskontrolle/Bild%207.png)](verwaltung-der-zugangskontrolle/Bild%207.png%3Fattredirects=0)
Bevor wir die Eigenschaften der Datenbank ansehen, sollten wir zunächst festlegen, wer auf die Datenbanken zugreifen darf. Zum Beispiel wäre denkbar, dass auf Datenbank 1 nur eingeloggte Benutzer/innen zugreifen dürfen - d.h. wir verknüpfen sie mit der Rolle "Normal user". Auf Datenbank 2 dürfen darüber hinaus auch nicht angemeldete  Benutzer/innen zugreifen, d.h., wir verknüpfen sowohl die "Normal user"-Rolle als auch die "Anonymous user"-Rolle mit Datenbank 2:
::: {style="display:block;text-align:left"}
[![](../_/rsrc/1409169553065/administration/verwaltung-der-zugangskontrolle/Bild%208.png)](verwaltung-der-zugangskontrolle/Bild%208.png%3Fattredirects=0)
Damit können nun die angelegten Nutzerobjekte, die wir mit dem "Normal user"-Rolle ausgestattet haben, auf beide Datenbanken zugreifen.
### []{#TOC-Neue-Datenbank-anlegen}Neue Datenbank anlegen
-   Um eine neue Datenbank anzulegen, klicken Sie auf das Plus-Zeichen links unten.
-   Geben Sie dann die ID für die neue Datenbank ein, d.h. eine Zeichenkette, die keine Sonder- oder Leerzeichen sowie keine Umlaute enthalten darf, z.B. "database3".
-   Die neue Datenbank erscheint dann in der Liste in der linken Spalte.
-   Klicken sie auf den neuen Eintrag und dann auf die Schaltfläche mit dem Stift ("Bearbeiten"). In dem nun erscheinenden Formular ändern Sie bitte nur die Felder "Name" und "Beschreibung", alle anderen Felder erfordern vertiefte Kenntnisse und können bei unsachgemäßer Änderung das Programm zum Abstürzen bringen.
-   Am Ende klicken Sie auf "OK" um die Änderungen zu speichern.
-   Am Ende müssen Sie die Datenbank noch freischalten. Wie bereits beschrieben, können Sie die Datenbank für eine bestimmte Rolle freischalten. Es ist aber auch möglich, sie einzelnen Nutzern oder Gruppen zuzuordnen.
:::
:::

::: {style="display:block;text-align:left;margin-left:40px"}
[](verwaltung-der-zugangskontrolle/Bild%201.png%3Fattredirects=0)
::: {style="display:block;text-align:left"}
[
](verwaltung-der-zugangskontrolle/Bild%204.png%3Fattredirects=0)
:::
:::
:::
