<?php
class nameparser_locale_en
{
  public function __construct( nameparser_NameParser $nameparser )
  {
    $nameparser->addRegExpr( array(
      "((?P<prefix>%s) )?" .  // optional prefix with trailing whitespace
      "((?P<first>\p{L}+) )"  .  // non-optional firstname with trailing whitespace
      "((?P<middle>.+?) )?" . // optional middle name(s) with trailing whitespace
      "(?P<last>%s)" .        // non-optional lastname with compounds
      "(,? (?P<suffix>%s))?"  // optional suffix
    ) );

    $nameparser->addPrefixes( array(
      'Mr.','Mister','Master','Mrs.','Ms.','Mr','Mrs','Ms',
      'Rev.', 'Rev',
      'Prof.','Prof','Dr.',"Dr"
    ) );

    // @todo move into separate locale files (dutch, italian, spanish, french etc.)
    $nameparser->addLastNameRegExpr( array(
      'vere \p{L}+','van \p{L}+','de \p{L}+',
      'del \p{L}+','della \p{L}+','di \p{L}+','da \p{L}+','pietro \p{L}+',
      'van den \p{L}+', 'van der \p{L}+',
      'du \p{L}+','st. \p{L}+','st \p{L}+','la \p{L}+',
      'ter \p{L}+',
      '\p{L}+ y \p{L}+', '\p{L}+ de \p{L}+'
    ) );

    $nameparser->addSuffixes( array(
      'I','II','III','IV','V',
      'Senior','Junior',
      'Jr.','Sr.','Jr','Sr',
      'Ph.D.','PhD',
      'APR','RPh','PE',
      'MD','MA','DMD','CME',
      'Esq.','Esq'
    ) );
  }
}
?>