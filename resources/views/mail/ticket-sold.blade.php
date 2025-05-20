@component('mail::message')

Hi {{$event->email ?? $event->title}},   

Great news! You have just received a payment for {{$ticketPayment->quantity}} {{$ticketPayment->eventPackage->title}} ticket sales from your event "{{$event->title}}".   

**Transaction Details:**   

**Amount Received:** NGN{{$paymentReference->amount}}   

**Date:** {{$ticketPayment->created_at->toDateTimeString()}}   

The payment has been successfully processed and credited to your account linked with our platform.   

Thank you for choosing ShopUrban to manage your event. If you have any questions or need further assistance, feel free to reach out to our support team.   

Best regards,  
**Shopurban Team**   
@endcomponent