search syntax
=============
::: {dir="ltr"}
### []{#TOC-1} [ ]{dir="ltr"} {#section align="left"}
[You can use the search function of Bibliograph in a really simple way, just like you are used to handling google search. Simply type all relevant search terms, seperated by spaces, into the search window.
]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
::: {dir="ltr"}
[
]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
*Example*:
::: {.sites-codeblock .sites-codesnippet-block}
[`Right Constitution`]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
:::
[ ]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
[**
**]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
[However, it is possible to create more complex queries.]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
[
]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
### []{#TOC-Feldnamen-und-Vergleichsoperatoren}Field names and comparison operators 
It is possible, to check fields for specific contents. These queries are implemented the following way:Es ist möglich, gezielt Felder auf spezielle Inhalte zu überprüfen. Diese Abfrage wird in der folgenden Form angegeben:

::: {.sites-codeblock .sites-codesnippet-block}
`field name comparison operator content`
:::

You can combine a random number of comparisons by seperating them with the boolean operator `and`.
#### []{#TOC-Beispiele:}*examples:*
::: {.sites-codeblock .sites-codesnippet-block}
[`title contains constitution and year = 1981 `]{style="line-height:1.25;font-size:10pt;background-color:transparent"}
:::
*
*
[ ]{style="color:rgb(0,96,0);font-family:monospace;background-color:rgb(239,239,239)"}
::: {.sites-codeblock .sites-codesnippet-block}
`author begins with w and keywords contains sociology of law`
:::
**
**
**Possible field names:**
[Author]{style="border:1px solid grey;padding:2px" value="Autor/in"} [Book title]{style="border:1px solid grey;padding:2px" value="Buchtitel"} [Date]{style="border:1px solid grey;padding:2px" value="Datum"} [Publisher]{style="border:1px solid grey;padding:2px" value="Herausgeber/in"} [Year]{style="border:1px solid grey;padding:2px" value="Jahr"} [Month]{style="border:1px solid grey;padding:2px" value="Monat"} [Place]{style="border:1px solid grey;padding:2px" value="Ort"} [Keywords]{style="border:1px solid grey;padding:2px" value="Schlagworte"} [Call Number]{style="border:1px solid grey;padding:2px" value="Signatur"} [Editor]{style="border:1px solid grey;padding:2px" value="Titel"} [Location]{style="border:1px solid grey;padding:2px" value="Verlag"} [Reftype]{style="border:1px solid grey;padding:2px" value="Werktyp"} [Journal]{style="border:1px solid grey;padding:2px" value="Zeitschrift"} [Citation Key]{style="border:1px solid grey;padding:2px" value="Zitierschlüssel"} [Abstract ]{style="border:1px solid grey;padding:2px" value="Zusammenfassung"}
#### []{#TOC-Possible-comparison-Operators:}**Possible comparison Operators:**
[<]{style="border:1px solid grey;padding:2px" value="<"} [<=]{style="border:1px solid grey;padding:2px" value="<="} [<>]{style="border:1px solid grey;padding:2px" value="<>"} [=]{style="border:1px solid grey;padding:2px" value="="} [>]{style="border:1px solid grey;padding:2px" value=">"} [>=]{style="border:1px solid grey;padding:2px" value=">="} [starts with]{style="border:1px solid grey;padding:2px" value="beginntmit"} [contains]{style="border:1px solid grey;padding:2px" value="enthält"} [does not contain]{style="border:1px solid grey;padding:2px" value="enthältnicht"} [is]{style="border:1px solid grey;padding:2px" value="ist"} [is not]{style="border:1px solid grey;padding:2px" value="istnicht"}
#### []{#TOC-M-gliche-Logische-Verkn-pfungen:}**Possible boolean operators:**
[and]{style="border:1px solid grey;padding:2px" value="und"}
[You can also use wildcard characters: `?` für a single character and `*` for any amount of characters.]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
#### []{#TOC-Beispiele:1}*Examples:*
[ ]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
::: {.sites-codeblock .sites-codesnippet-block}
`author contains m*ller`
:::
[ (finds müller, mueller, miller) ]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
[
]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
::: {.sites-codeblock .sites-codesnippet-block}
[`title contains democrati?ation `]{style="color:rgb(0,96,0);font-family:arial,sans-serif;line-height:1.25;font-size:10pt;background-color:rgb(239,239,239)"}[` `]{style="font-family:arial,sans-serif;color:rgb(0,96,0);font-size:10pt;line-height:1.25;background-color:transparent"}
:::
[(finds democratization and democratisation)]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
  [If you use more than one word, you have to put the expression into inverted commas:]{style="line-height:1.25;font-size:10pt;background-color:transparent"}
[]{style="font-size:10pt;line-height:1.25;background-color:transparent"}
::: {.sites-codeblock .sites-codesnippet-block}
`title begins with "new developsments" and keywords does not contain "public law"`
:::
[If you choose the black question mark next to the search operator, an interactive help function will open. You can click on the framed terms and they will automatically be added into the search field.]{style="line-height:1.25;font-size:10pt;background-color:transparent"}[
]{style="line-height:1.25;font-size:10pt;background-color:transparent"}
:::
:::
