<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Routing\Redirector;
use App\Http\Requests\StoreMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Notifications\MessageReceived;
use App\Repository\ConversationRepository;
use App\Models\Message;
// use App\Http\Controllers\Controller;
// use App\Http\Requests\User\UserRequest;
// use Illuminate\Support\Facades\Crypt;

class ConversationsController extends Controller
{
    /**
     * @var ConversationRepository
     */
    private $r;

    public function __construct (ConversationRepository $conversationRepository, AuthManager $auth) {
        $this->middleware('auth'); /** renvoyer vers page de connexion si utilisateur déconnecté */
        $this->r = $conversationRepository;
        $this->auth = $auth;
    }


    public function index() {

        return view('conversations/index', [
            'users' => $this->r->getConversations($this->auth->user()->id),
            'unread'=> $this->r->unreadCount($this->auth->user()->id)
        ]);
    }
    
    public function show (User $user) {
        $me = $this->auth->user();
        $messages = $this->r->getMessagesFor($me->id, $user->id)->paginate(5);
        $unread = $this->r->unreadCount($me->id);

        if (isset($unread[$user->id])) {
            $this->r->readAllFrom($user->id, $me->id);
            unset($unread[$user->id]); /**car isset puis permet d'enlever le 0 lorsque pas de notifications */
        }
        return view('conversations/show', [
            'users' => $this->r->getConversations($this->auth->user()->id),
            'user' => $user,
            'messages' => $messages,
            'unread'=> $unread
        ]);
    }
    public function destroy(Request $request){

        $id = $request->input('messageId');
            var_dump($id);
                Message::find($id)->delete();   
                return back();
    }

    public function store (User $user, StoreMessage $request) {
        $message = $this->r->createMessage(
            $request->get('content'),
            $this->auth->user()->id,
            $user->id
        );
        $user->notify(new MessageReceived($message));
        return redirect()->route('conversations.show', [$user->id]);
    }
}
