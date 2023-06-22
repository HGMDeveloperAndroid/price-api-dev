<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;
    public $data;

    /**
     * Create a new message instance.
     *
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address = 'pedro.rojas@gmail.com';
        $subject = 'Priceheck: Nuevo usuario Registrado';
        $name = 'Jane Doe';
        $url = 'https://pricecheck-dashboard.bnomio.dev/login';

        return $this->view('emails.test')
            ->from($address, $name)
            ->subject($subject)
            ->with([
                'username' => $this->data['username'],
                'url' => $url
            ]);
    }
}
