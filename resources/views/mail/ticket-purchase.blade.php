@component('mail::message')

Dear {{$ticketPayment->name}},

Thank you for booking with us. Here are your event details

@component('mail::panel')
<b>Match:</b> {{$event->title}}  
<b>Date:</b>&nbsp; &nbsp; {{$event->start_time->toFormattedDayDateString()}}  
<b>Venue:</b> Nnamdi Azikiwe Stadium (The Cathedral), Ogui Road, Enugu  
@endcomponent
    
Your reservation has been ticketed and your seats are confirmed.   

<div style="text-align: center;">{!!$ticket->qr_code!!}</div>   
<br>
If you have any queries about your booking, click <a href="#">here to view our Help pages.</a>   

We hope you have a pleasant event experence.   

{{-- Click Here to View & Print Online    --}}

Thank you for choosing shopurban!   

Best Regards
@endcomponent