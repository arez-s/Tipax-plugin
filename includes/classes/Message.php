<?php

class Message
{
    const SUCCESS = 1;
    const ERROR   = 2;

     public function __construct()
     {
         add_action( 'admin_notices', [$this, 'default'] );
     }
    
     public function add(string $message, string $type = self::SUCCESS)
     {
         $_SESSION['tsm_messages'] = [
                 'message'   => $message,
                 'type'      => $type
         ];
     }

     public function show()
     {
         if (isset($_SESSION['tsm_messages'])) {
             $message = $_SESSION['tsm_messages'];
             if ($message['type'] == self::SUCCESS) {
                 echo '<div class="alert alert-success"><p>' . $message['message'] . '</p></div>';
             } else {
                 echo '<div class="alert alert-danger"><p>' . $message['message'] . '</p></div>';
             }

             self::clear();
         }
     }

     public function get()
     {
         $messageInHtml = '';
         if (isset($_SESSION['tsm_messages'])) {
             $messageInHtml = $_SESSION['tsm_messages'];
             if ($messageInHtml['type'] == self::SUCCESS) {
                 $messageInHtml = '<div class="alert alert-success"><p>' . $messageInHtml['message'] . '</p></div>';
             } else {
                 $messageInHtml = '<div class="alert alert-danger"><p>' . $messageInHtml['message'] . '</p></div>';
             }

             self::clear();
         }

         return $messageInHtml;
     }

     public function clear()
     {
         $_SESSION['tsm_messages'] = null;
     }

    public function default($message, $type = 0, $html = null)
    {
        $class = 'notice notice-error is-dismissible';
        if ($type == 1) {
            $class = 'notice notice-success is-dismissible';         
        }
        if($message){
            printf( '<div class="%1$s"><p>%2$s</p>%3$s</div>', esc_attr( $class ), esc_html( $message ),$html );
        }
    }
}