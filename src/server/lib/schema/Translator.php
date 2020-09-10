<?php

namespace lib\schema;

class Translator extends SchemaItem {

  public Schema $fromSchema;

  public Schema $toSchema;

  public SchemaItem $fromItem;

  public SchemaItem $toItem;

}
