<?php
$startTime = $event->start_time->format('h:i A');
$startDate = $event->start_time->toFormattedDayDateString();
?>

<div class="ticket-container">
    <!-- Left Section -->
    <div class="ticket-left">
        <div class="ticket-left-content">
            <p class="access">{{$eventPackage->title}}<br>ACCESS</p>
            <p class="date">{{$event->start_time->toDateString()}} | {{$startTime}}</p>
            <p class="section">SECTION: {{$seatSection->title}}</p>
            <p class="seat">SEAT: {{$seat->seat_no}}</p>
            <p class="gate">GATE: {{$eventPackage->entry_gate}}</p>
        </div>
    </div>

    <!-- Right Section -->
    <div class="ticket-right">
        <div class="ticket-right-content">
            <h2 class="event-title">{{$event->title}}</h2>
            <p class="event-time"><span>{{$startDate}}</span> | <span>{{$startTime}}</span></p>
            <p class="event-location">{{$event->venue}}</p>

            <div class="ticket-info">
                <div>
                    <span>SECTION</span>
                    <p>{{$seatSection->title}}</p>
                </div>
                <div>
                    <span>SEAT</span>
                    <p>{{$seat->seat_no}}</p>
                </div>
                <div>
                    <span>GATE</span>
                    <p>{{$eventPackage->entry_gate}}</p>
                </div>
            </div>
        </div>
        <div class="qr-code">
            <!-- Dynamic QR code -->
            <div class="[&amp;&gt;svg]:w-5.6 [&amp;&gt;svg]:h-5.6">
                {!! $ticket->qr_code !!}
                {{-- <img src="{{asset('build/img.png')}}" alt=""> --}}
                {{-- {!! $qrCode !!} --}}
                {{-- <img src="{!! $qrCode !!}" alt="QR Code"> --}}
                {{-- <svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100" viewBox="0 0 100 100"><rect x="0" y="0" width="100" height="100" fill="#ffffff"></rect><g transform="scale(3.448)"><g transform="translate(0,0)"><path fill-rule="evenodd" d="M9 0L9 1L8 1L8 2L9 2L9 3L8 3L8 4L10 4L10 7L11 7L11 4L12 4L12 5L13 5L13 6L12 6L12 9L14 9L14 10L11 10L11 11L10 11L10 9L11 9L11 8L6 8L6 9L5 9L5 8L0 8L0 10L1 10L1 9L3 9L3 12L5 12L5 15L7 15L7 14L8 14L8 17L10 17L10 18L11 18L11 19L10 19L10 20L9 20L9 19L8 19L8 18L6 18L6 17L7 17L7 16L6 16L6 17L3 17L3 16L2 16L2 17L3 17L3 19L2 19L2 20L3 20L3 21L5 21L5 20L6 20L6 21L8 21L8 23L9 23L9 24L8 24L8 29L9 29L9 27L10 27L10 28L11 28L11 29L12 29L12 27L13 27L13 26L11 26L11 25L10 25L10 26L9 26L9 24L10 24L10 23L9 23L9 22L10 22L10 20L11 20L11 21L12 21L12 22L13 22L13 23L12 23L12 25L13 25L13 24L14 24L14 25L16 25L16 26L15 26L15 27L14 27L14 28L15 28L15 29L16 29L16 27L17 27L17 26L18 26L18 28L17 28L17 29L18 29L18 28L21 28L21 29L24 29L24 28L25 28L25 27L26 27L26 26L24 26L24 25L27 25L27 28L26 28L26 29L27 29L27 28L28 28L28 26L29 26L29 24L25 24L25 23L26 23L26 21L28 21L28 22L27 22L27 23L28 23L28 22L29 22L29 20L26 20L26 17L27 17L27 18L29 18L29 17L27 17L27 16L29 16L29 15L27 15L27 16L25 16L25 15L26 15L26 14L29 14L29 13L28 13L28 12L25 12L25 11L28 11L28 10L29 10L29 9L28 9L28 8L27 8L27 9L28 9L28 10L26 10L26 8L25 8L25 9L24 9L24 8L23 8L23 9L22 9L22 8L20 8L20 9L19 9L19 8L17 8L17 9L16 9L16 8L15 8L15 9L14 9L14 8L13 8L13 6L14 6L14 7L15 7L15 6L16 6L16 7L17 7L17 6L16 6L16 5L21 5L21 4L19 4L19 3L17 3L17 2L16 2L16 1L18 1L18 2L19 2L19 1L20 1L20 0L19 0L19 1L18 1L18 0L16 0L16 1L15 1L15 0L13 0L13 1L11 1L11 2L10 2L10 0ZM13 1L13 2L12 2L12 4L13 4L13 5L14 5L14 6L15 6L15 5L16 5L16 4L17 4L17 3L16 3L16 2L14 2L14 1ZM20 2L20 3L21 3L21 2ZM10 3L10 4L11 4L11 3ZM13 3L13 4L14 4L14 5L15 5L15 4L16 4L16 3L15 3L15 4L14 4L14 3ZM8 5L8 7L9 7L9 5ZM18 6L18 7L19 7L19 6ZM20 6L20 7L21 7L21 6ZM6 9L6 10L7 10L7 9ZM15 9L15 10L16 10L16 11L17 11L17 12L18 12L18 10L19 10L19 11L20 11L20 12L19 12L19 13L15 13L15 12L14 12L14 11L12 11L12 13L11 13L11 12L10 12L10 13L11 13L11 15L9 15L9 16L10 16L10 17L11 17L11 18L13 18L13 19L12 19L12 21L14 21L14 22L16 22L16 21L17 21L17 23L16 23L16 25L17 25L17 24L19 24L19 25L18 25L18 26L20 26L20 27L21 27L21 26L23 26L23 27L22 27L22 28L23 28L23 27L24 27L24 26L23 26L23 25L21 25L21 26L20 26L20 23L18 23L18 21L20 21L20 18L21 18L21 19L22 19L22 20L23 20L23 18L22 18L22 17L25 17L25 16L23 16L23 15L25 15L25 14L26 14L26 13L25 13L25 12L24 12L24 11L25 11L25 10L24 10L24 9L23 9L23 11L21 11L21 10L22 10L22 9L20 9L20 10L19 10L19 9L17 9L17 10L16 10L16 9ZM1 11L1 12L2 12L2 11ZM6 11L6 12L7 12L7 11ZM13 12L13 13L12 13L12 14L13 14L13 15L12 15L12 17L13 17L13 18L15 18L15 19L14 19L14 20L15 20L15 21L16 21L16 19L17 19L17 17L18 17L18 19L19 19L19 18L20 18L20 16L21 16L21 17L22 17L22 16L21 16L21 14L22 14L22 15L23 15L23 14L25 14L25 13L22 13L22 12L21 12L21 13L19 13L19 14L20 14L20 16L19 16L19 17L18 17L18 16L17 16L17 15L16 15L16 14L15 14L15 13L14 13L14 12ZM1 13L1 14L0 14L0 15L1 15L1 14L3 14L3 13ZM6 13L6 14L7 14L7 13ZM8 13L8 14L9 14L9 13ZM13 13L13 14L14 14L14 15L13 15L13 16L14 16L14 17L15 17L15 18L16 18L16 17L17 17L17 16L16 16L16 17L15 17L15 14L14 14L14 13ZM0 17L0 21L1 21L1 17ZM4 18L4 20L5 20L5 18ZM6 19L6 20L8 20L8 19ZM17 20L17 21L18 21L18 20ZM25 20L25 21L26 21L26 20ZM21 21L21 24L24 24L24 21ZM22 22L22 23L23 23L23 22ZM14 23L14 24L15 24L15 23ZM0 0L0 7L7 7L7 0ZM1 1L1 6L6 6L6 1ZM2 2L2 5L5 5L5 2ZM22 0L22 7L29 7L29 0ZM23 1L23 6L28 6L28 1ZM24 2L24 5L27 5L27 2ZM0 22L0 29L7 29L7 22ZM1 23L1 28L6 28L6 23ZM2 24L2 27L5 27L5 24Z" fill="#000000"></path></g></g></svg> --}}
            </div>
        </div>
    </div>
</div>


<style>
.ticket-container {
    display: flex;
    border: 1px solid #ccc;
    border-radius: 8px;
    overflow: hidden;
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    background: #fff;
}

/* Left Section */
.ticket-left {
    width: 30%;
    border-right: 2px dashed #999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.ticket-left-content {
    text-align: center;
    transform: rotate(90deg);
    white-space: nowrap;
    font-weight: 100;
    font-family: 'verdana';
    color: #777;
    font-size: 14px;
    max-lines: 1;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ticket-left-content p {
    margin: 10px 0;
    /* padding: 0px 10px; */
}
.ticket-left-content .access {
    font-size: 18px;
    /* font-weight: bold; */
    margin-bottom: 16px;
    text-transform: uppercase
}

/* Right Section */
.ticket-right {
    width: 70%;
    padding: 20px 30px;
    display: flex;
    align-items: center;
}
.ticket-right-content {
    width: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.event-title {
    color: #f44336;
    font-size: 24px;
    font-weight: bold;
    margin: 0 0 10px;
}
.event-time {
    font-size: 14px;
    margin: 0 0 5px;
    color: #333;
}
.event-location {
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 20px;
    color: #555;
}
.ticket-info {
    display: flex;
    gap: 40px;
    margin-bottom: 20px;
}
.ticket-info span {
    font-size: 12px;
    color: #777;
    display: block;
}
.ticket-info p {
    font-weight: bold;
    margin: 5px 0 0;
    font-size: 14px;
}
.qr-code {
    align-self: flex-end;
}
.qr-code img {
    width: 80px;
    height: 80px;
}
</style>
