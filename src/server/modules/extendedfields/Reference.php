<?php

namespace app\modules\extendedfields;

use Yii;

/**
 * @inheritdoc
 * @property string $_category
 * @property string $_owner
 * @property string $_source
 * @property string $_sponsor
 * @property string $_date_ordered
 * @property string $_date_received
 * @property string $_date_reimbursement_requested
 * @property string $_inventory
 */
class Reference extends \app\models\Reference
{

    /**
     * @inheritdoc
     */
    public function rules()
    {
      return array_merge(
        parent::rules(),
        [
          [['_date_ordered', '_date_received', '_date_reimbursement_requested'], 'safe'],
          [['_owner', '_sponsor', '_inventory'], 'string', 'max' => 50],
          [['_source'], 'string', 'max' => 255],
          [['_category'], 'string', 'max' => 100]
        ]
      );
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
      return array_merge(
        parent::rules(),
        [
          '_category' => Yii::t('plugin.extendedfields', 'Category'),
          '_owner' => Yii::t('plugin.extendedfields', 'Owner'),
          '_source' => Yii::t('plugin.extendedfields', 'Source'),
          '_sponsor' => Yii::t('plugin.extendedfields', 'Sponsor'),
          '_date_ordered' => Yii::t('plugin.extendedfields', 'Date Ordered'),
          '_date_received' => Yii::t('plugin.extendedfields', 'Date Received'),
          '_date_reimbursement_requested' => Yii::t('plugin.extendedfields', 'Date Reimbursement Requested'),
          '_inventory' => Yii::t('plugin.extendedfields', 'Inventory'),
        ]
      );
    }

  /**
   * Returns the schema object used by this model
   * @return \app\schema\BibtexSchema
   */
  public static function getSchema()
  {
    static $schema = null;
    if (is_null($schema)) {
      $schema = new ReferenceSchema();
    }
    return $schema;
  }
}
