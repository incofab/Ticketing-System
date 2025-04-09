@component('mail::message')

Dear {{$ticketPayment->name}},

Thank you for booking with us. Here are your event details

@component('mail::panel')
<div style="margin-bottom: 5px;"><b>Event:</b> {{$event->title}}</div>
<div><b>Date:</b>&nbsp; {{$event->start_time->toFormattedDayDateString()}}</div>
{{-- <b>Venue:</b> Nnamdi Azikiwe Stadium (The Cathedral), Ogui Road, Enugu   --}}
@endcomponent
    
Your reservation has been ticketed and your seats are confirmed.   

<div style="text-align: center;">{!!$ticket->qr_code!!}</div>   
<br>
<div>
If you have any queries about your booking, click <a href="#">here for more.</a>   
</div>

We hope you have a pleasant event experence.   

{{-- Click Here to View & Print Online    --}}

Thank you for choosing shopurban!   

Best Regards
@endcomponent