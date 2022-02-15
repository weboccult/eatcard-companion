### Inspired By

- This eatcardSms Service is highly inspired by : https://github.com/tzsk/sms
- You can go there check it out by your self.

#### Pros :
 - Now as I have migrated to custom service provider we can change the code without waiting for package maintainer
 to fix the issue or add new driver that we want.
 - Elegant and clean syntax

#### Cons :
 - You need to be familiar and must have deep knowledge of Oops Concepts otherwise you can't be able to add more sms 
 - drivers and also you can't able to change the code / fix the issues.


### Publish sms config file.

```php
php artisan eatcardsms:publish --type=config
```

### Publish sms migration file.

```php
php artisan eatcardsms:publish --type=migration
```
It will publish migration or config files to your parent project and after that you can override message in parent 
project where this package is installed.

------------------------------

### Installation Steps


1. Install companion package by running "composer require weboccult/eatcard-companion"

2. Publish Config & Migration file as mentioned above

3. you're good to go.

------------------------------

### Installation Steps

```php

#------------------------------
# Normal Usage
#------------------------------

use Weboccult\EatcardCompanion\Services\Facades\EatcardSms;

EatcardSms::send("this message") // required
    ->type("string") // required
    ->responsible($model) // it will accept only Eloquent Model instance // this optional
    ->channel($channel)
    ->storeId($model) // it will accept (Eloquent Model instance) or (Id as integer|string) // this optional
    ->to(['Number 1', 'Number 2'])  // required
    ->dispatch();  // required

# If you want to use a different driver.
EatcardSms::via('gateway')
    ->send("this message")
    ->to(['Number 1', 'Number 2'])
    ->dispatch();
# Here gateway is explicit : 'twilio' or 'CUSTOM' or any other driver in the config.
# The numbers can be a single string as well.

#------------------------------
# Using Helper function
#------------------------------

use function \Weboccult\EatcardCompanion\Helpers\{eatcardSms};

eatcardSms()->send("this message") // required
    ->type("string") // required
    ->responsible($model) // it will accept only Eloquent Model instance // this optional
    ->storeId($model) // it will accept (Eloquent Model instance) or (Id as integer) // this optional
    ->to(['Number 1', 'Number 2'])  // required
    ->dispatch();  // required

eatcardSms()->via('gateway')
    ->send("this message")
    ->to(['Number 1', 'Number 2'])
    ->dispatch();
    
#------------------------------
# Channel Usage
#------------------------------
# (1) First you have to create your notification using php artisan make:notification command
# (2) SmsChannel::class can be used as channel like the below..


#------------------------------
# Notification Class
#------------------------------
namespace App\Notifications;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Weboccult\EatcardCompanion\Services\Common\Sms\Builder;
use Weboccult\EatcardCompanion\Services\Common\Sms\SmsChannel;

class SendTestSms extends Notification
{
    use Queueable;
    
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    
    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [SmsChannel::class];
    }
    
    /**
     * Get the recipients and body of the notification.
     *
     * @param  mixed  $notifiable
     * @return Builder
     */
    public function toSms($notifiable)
    {
        return (new Builder)
                ->via('gateway-driver') # via() is Optional
               ->send('Test message from notification channel')
               ->to(['111111', '222222']);
    }
    }
    public function toSms($notifiable)
    {
        return (new Builder)
            ->via('gateway') # via() is Optional
            ->send('this message')
            ->to('some number');
    }
}

#------------------------------
# Direct Notification
#------------------------------

Notification::route(SmsChannel::class, 'sms')->notify(new SendTestSms([111111]));


#------------------------------
# Via Notify Trait
#------------------------------
# (1) You need to add Notifiable trait in your model so you can access ->notify method from your model.
#let's say I am using User model :

class User extends Model
{
use Notifiable;

# (2) You to add one if condition so our smsChannel will know in which field mobile number is there..

public function toSms($notifiable)
{
if ($notifiable instanceof User) {
    $this->recipients = [str_replace(' ', '', $notifiable->contact_no)];
}

# Final Code will look like below :

$user = \App\Models\User::first();
$user->notify(new \App\Notifications\SendTestSms());

```
