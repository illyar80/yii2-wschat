<?php
namespace jones\wschat\components;

use Yii;
use yii\helpers\Json;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/**
 * Class Chat
 * @package \jones\wschat\components
 */
class Chat implements MessageComponentInterface
{
    /** @var ConnectionInterface[] */
    protected $clients = [];
    /** @var \jones\wschat\components\ChatManager */
    protected $cm = null;
    /**
     * @var array list of available requests
     */
    protected $requests = [
        'auth', 'message'
    ];

    /**
     * @param \jones\wschat\components\ChatManager $cm
     */
    public function __construct(ChatManager $cm)
    {
        $this->cm = $cm;
    }

    /**
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $rid = $this->getResourceId($conn);
        $this->clients[$rid] = $conn;
        Yii::info('Connection is established: '.$rid, 'chat');
    }

    /**
     * @param ConnectionInterface $from
     * @param string $msg
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = Json::decode($msg, true);
        $rid = array_search($from, $this->clients);
        if (in_array($data['type'], $this->requests)) {
            call_user_func_array([$this, $data['type'].'Request'], [$rid, $data['data']]);
        }
    }

    /**
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn)
    {
        $rid = array_search($conn, $this->clients);
        if ($this->cm->getUserByRid($rid)) {
            $this->closeRequest($rid);
        }
        unset($this->clients[$rid]);
        Yii::info('Connection is closed: '.$rid, 'chat');
    }

    /**
     * @param ConnectionInterface $conn
     * @param \Exception $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Yii::error($e->getMessage());
        $conn->send(Json::encode(['type' => 'error', 'data' => [
            'message' => Yii::t('app', 'Something wrong. Connection will be closed')
        ]]));
        $conn->close();
    }

    /**
     * Get connection resource id
     *
     * @access private
     * @param ConnectionInterface $conn
     * @return string
     */
    protected function getResourceId(ConnectionInterface $conn)
    {
        return $conn->resourceId;
    }

    /**
     * Process auth request. Find user chat(if not exists - create it)
     * and send message to all other clients
     *
     * @access private
     * @param $rid
     * @param $data
     * @return void
     */
    protected function authRequest($rid, array $data)
    {
        $chatId = $data['cid'];
        Yii::info('Auth request from rid: '.$rid.' and chat: '.$chatId, 'chat');
        $userId = !empty($data['user']['id']) ? $data['user']['id'] : '';
        //the same user already connected to current chat, need to close old connect
        if ($oldRid = $this->cm->isUserExistsInChat($userId, $chatId)) {
            $this->closeRequest($oldRid);
        }
        $this->cm->addUser($rid, $userId, $data['user']);
        $chat = $this->cm->findChat($chatId, $rid);
        $users = $chat->getUsers();
        $joinedUser = $this->cm->getUserByRid($rid);
        $response = [
            'user' => $joinedUser,
            'join' => true,
        ];
        foreach ($users as $user) {
            //send message for other users of this chat
            if ($userId != $user->getId()) {
                $conn = $this->clients[$user->getRid()];
                $conn->send(Json::encode(['type' => 'auth', 'data' => $response]));
            }
        }
        //send auth response for joined user
        $response = [
            'user' => $joinedUser,
            'users' => $users,
            'history' => $this->cm->getHistory($chat->getUid())
        ];
        $conn = $this->clients[$rid];
        $conn->send(Json::encode(['type' => 'auth', 'data' => $response]));
    }

    /**
     * Process message request. Find user chat room and send message to other users
     * in this chat room
     *
     * @access private
     * @param $rid
     * @param array $data
     * @return void
     */
    protected function messageRequest($rid, array $data)
    {
        Yii::info('Message from: '.$rid, 'chat');
        $chat = $this->cm->getUserChat($rid);
        if (!$chat) {
            return;
        }
        $data['message']['timestamp'] = time();
        $user = $this->cm->getUserByRid($rid);
        $this->cm->storeMessage($user, $chat, $data['message']);
        foreach ($chat->getUsers() as $user) {
            //need not to send message for self
            if ($user->getRid() == $rid) {
                continue;
            }
            $conn = $this->clients[$user->getRid()];
            $conn->send(Json::encode(['type' => 'message', 'data' => $data]));
        }
    }

    /**
     * Process close request. Find user chat, remove user from chat and send message
     * to other users in this chat
     *
     * @access public
     * @param $rid
     */
    protected function closeRequest($rid)
    {
        //get user for closed connection
        $requestUser = $this->cm->getUserByRid($rid);
        $chat = $this->cm->getUserChat($rid);
        //remove user from chat room
        $this->cm->removeUserFromChat($rid);
        //send notification for other users in this chat
        $users = $chat->getUsers();
        $response = array(
            'type' => 'close',
            'data' => ['user' => $requestUser]
        );
        foreach ($users as $user) {
            $conn = $this->clients[$user->getRid()];
            $conn->send(Json::encode($response));
        }
    }
}

 
