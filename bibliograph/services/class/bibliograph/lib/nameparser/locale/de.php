<?php

class nameparser_locale_de
{
  public function __construct( nameparser_NameParser $nameparser )
  {
    $nameparser->addPrefixes( array(
      'Herr','Frau',
      'Dr. med.', 'Dr. iur', 'Dr. habil.', 'Dr.',
      'PD Dr.',
      'Prof. Dr.','Prof. em.'
    ) );

    $nameparser->addLastNameRegExpr( array(
      'von \p{L}+','zu \p{L}+'
    ) );

  }
}
?>