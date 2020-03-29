<?php
namespace jones\wschat\components;

use Yii;
use jones\wschat\collections\History;

/**
 * Class AbstractStorage
 *
 * Base class to create concrete implementer of message storing
 * @package jones\wschat\components
 */
abstract class AbstractStorage
{
    /**
     * Create instance of storage
     *
     * @access public
     * @static
     * @param string $storage default null
     * @return \jones\wschat\components\AbstractStorage
     */
    public static function factory($storage = null)
    {
        if (empty($storage)) {
            $storage = Yii::$app->getDb()->driverName;
        }
        switch ($storage) {
            default:
                $class = new DbStorage();
        }
        return $class;
    }

    /**
     * Load chat history
     *
     * @access public
     * @param mixed $chatId
     * @param integer $limit
     * @return array
     */
    abstract public function getHistory($chatId, $limit = 10);

    /**
     * Store chat message
     *
     * @access public
     * @param array $params
     * @return boolean
     */
    abstract public function storeMessage(array $params);
}

