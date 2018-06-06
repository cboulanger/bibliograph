Suchsyntax
==========
::: {dir="ltr"}
[Sie können die Suchfunktion dieser Anwendung auf ganz einfache Art und Weise nutzen, wie Sie es von der Google-Suche gewohnt sind. Tippen Sie dafür einfach alle relevanten Suchbegriffe mit Leerzeichen getrennt in das Suchfeld. ]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
[
]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
[*Beispiel:*]{style="font-size:10pt;line-height:1.25;background-color:transparent"}

::: {.sites-codeblock .sites-codesnippet-block}
[`Recht Verfassung`]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
:::
[ ]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
[**
**]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
[Es ist aber auch möglich, komplexere Abfragen zu erstellen.]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
[
]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
### []{#TOC-Feldnamen-und-Vergleichsoperatoren}Feldnamen und Vergleichsoperatoren
Es ist möglich, gezielt Felder auf spezielle Inhalte zu überprüfen. Diese Abfrage wird in der folgenden Form angegeben:


::: {.sites-codeblock .sites-codesnippet-block}
`Feldname Vergleichsoperator Inhalt`
:::

Dabei können beliebig viele Vergleiche kombiniert werden, indem sie durch die logische Verknüpfung `und` getrennt hintereinander geschrieben werden:

#### []{#TOC-Beispiele:}*Beispiele:*
::: {.sites-codeblock .sites-codesnippet-block}
[`titel enthält verfassung und jahr = 1981`]{style="line-height:1.25;font-size:10pt;background-color:transparent"}
:::
*
*
[ ]{style="color:rgb(0,96,0);font-family:monospace;background-color:rgb(239,239,239)"}
::: {.sites-codeblock .sites-codesnippet-block}
`autor beginnt mit w und schlagworte enthält rechtssoziologie`
:::

**
**
**Mögliche Feldnamen:**
[Autor/in]{style="border:1px solid grey;padding:2px" value="Autor/in"} [Buchtitel]{style="border:1px solid grey;padding:2px" value="Buchtitel"} [Datum]{style="border:1px solid grey;padding:2px" value="Datum"} [Herausgeber/in]{style="border:1px solid grey;padding:2px" value="Herausgeber/in"} [Jahr]{style="border:1px solid grey;padding:2px" value="Jahr"} [Monat]{style="border:1px solid grey;padding:2px" value="Monat"} [Ort]{style="border:1px solid grey;padding:2px" value="Ort"} [Schlagworte]{style="border:1px solid grey;padding:2px" value="Schlagworte"} [Signatur]{style="border:1px solid grey;padding:2px" value="Signatur"} [Titel]{style="border:1px solid grey;padding:2px" value="Titel"} [Verlag]{style="border:1px solid grey;padding:2px" value="Verlag"} [Werktyp]{style="border:1px solid grey;padding:2px" value="Werktyp"} [Zeitschrift]{style="border:1px solid grey;padding:2px" value="Zeitschrift"} [Zitierschlüssel]{style="border:1px solid grey;padding:2px" value="Zitierschlüssel"} [Zusammenfassung]{style="border:1px solid grey;padding:2px" value="Zusammenfassung"}
#### []{#TOC-M-gliche-Vergleichsoperatoren:}**Mögliche Vergleichsoperatoren:**
[<]{style="border:1px solid grey;padding:2px" value="<"} [<=]{style="border:1px solid grey;padding:2px" value="<="} [<>]{style="border:1px solid grey;padding:2px" value="<>"} [=]{style="border:1px solid grey;padding:2px" value="="} [>]{style="border:1px solid grey;padding:2px" value=">"} [>=]{style="border:1px solid grey;padding:2px" value=">="} [beginnt mit]{style="border:1px solid grey;padding:2px" value="beginntmit"} [enthält]{style="border:1px solid grey;padding:2px" value="enthält"} [enthält nicht]{style="border:1px solid grey;padding:2px" value="enthältnicht"} [ist]{style="border:1px solid grey;padding:2px" value="ist"} [ist nicht]{style="border:1px solid grey;padding:2px" value="istnicht"}
#### []{#TOC-M-gliche-Logische-Verkn-pfungen:}**Mögliche Logische Verknüpfungen:**
[und]{style="border:1px solid grey;padding:2px" value="und"}
[Sie können auch Ersetzungszeichen verwenden: `?` für einen einzelnen Buchstaben und `*` für eine beliebige Anzahl von Buchstaben.]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
#### []{#TOC-Beispiele:1}*Beispiele:*
[ ]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
::: {.sites-codeblock .sites-codesnippet-block}
`autor enthält m*ller`
:::
[ (findet müller, mueller, miller) ]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
[
]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
::: {.sites-codeblock .sites-codesnippet-block}
[`titel enthält democrati?ation `]{style="color:rgb(0,96,0);font-family:arial,sans-serif;line-height:1.25;font-size:10pt;background-color:rgb(239,239,239)"}[` `]{style="font-family:arial,sans-serif;color:rgb(0,96,0);font-size:10pt;line-height:1.25;background-color:transparent"}

:::
[(findet democratization und democratisation)]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
[ Wenn Sie mehr als ein Wort verwenden, müssen sie den Ausdruck mit Anführungszeichen einschließen:]{style="line-height:1.25;font-size:10pt;background-color:transparent"}
[]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
::: {.sites-codeblock .sites-codesnippet-block}
`titel beginnt mit "Neuere Entwicklungen" und schlagwörter enthält nicht "öffentliches recht"`
:::
[Eine interaktive Hilfsfunktion wird angezeigt, wenn Sie auf das schwarze Fragezeichen oben rechts klicken. In dem nun erscheinenden Fenster können Sie auf die einzelnen eingerahmten Begriffe klicken, sie werden dann in das Suchfeld eingefügt.]{style="line-height:1.25;font-size:10pt;background-color:transparent"}[ ]{style="line-height:1.25;font-size:10pt;background-color:transparent"}
:::
