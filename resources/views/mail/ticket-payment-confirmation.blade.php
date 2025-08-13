@component('mail::message')

Dear {{$ticketPayment->name}},

Thank you for booking with us. Below are your event details:  

@component('mail::panel')
<div style="margin-bottom: 7px;"><b>Event:</b> {{$event->title}}</div>
<div style="margin-bottom: 7px;"><b>Date:</b>&nbsp; {{$event->start_time->toFormattedDayDateString()}}</div>
<div><b>Venue:</b> {{$event->venue}}</div>
@endcomponent
    
Your payment has been confirmed.   
<div style="margin-bottom: 7px;"><b>Amount:</b> {{number_format($paymentReference->amount)}}</div>
<div style="margin-bottom: 7px;"><b>Quantity:</b> {{number_format($paymentReference->amount)}}</div>
<div style="margin-bottom: 7px;"><b>Package:</b> {{$eventPackage->title}}</div>

To view your ticket and confirm your attendance, RSVP at <a href="{{$callbackUrl}}">{{$callbackUrl}}</a> 


If you found this email in your Spam folder, please "Report not Spam".  

We hope you have a memorable experience at the event.   

Best Regards
@endcomponent