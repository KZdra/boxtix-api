<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            display: block;
            width: 100%;
            height: 100%;
        }

        .container {
            width: 90%; /* Set container width to 90% for narrow margin */
            max-width: 800px; /* Max width to ensure it doesn't get too wide */
            margin: 0 auto; /* Center the container */
            padding: 20px;
            background: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 12px; /* Add border-radius for smooth corners */
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .banner {
            width: 100%;
            height: 300px;
            border-radius: 12px;
        }

        .ticket-details {
            background-color: #e2e2e2;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .ticket-details td {
            padding: 8px;
            vertical-align: top;
        }

        .ticket-details td.left {
            width: 50%;
        }

        .ticket-details td.right {
            width: 50%;
        }

        .ticket-details h2 {
            margin-top: 0;
            font-size: 24px;
            color: #333;
        }

        .ticket-details p {
            font-size: 16px;
            color: #222;
            line-height: 1.5;
        }

        .ticket-details p strong {
            color: #555;
        }

        .qr-code {
            border-top: 2px dashed black;
            text-align: center;
            margin-top: 30px;
        }

        .qr-code img {
            width: 150px;
            height: 150px;
        }

        .footer {
            text-align: center;
            font-size: 14px;
            margin-top: 20px;
            border-top: 2px dashed black;
            color: #000000;
        }

        .line-break {
            border-top: 2px dashed black;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <img class="banner" src="{{ public_path('storage/'.$ticketData->banner_path) }}" alt="Event Banner">
        </div>

        <div class="ticket-details">
            <h2>{{$ticketData->event_name}} </h2>
            <p><strong>Location:</strong> {{ $ticketData->location}}</p>
            <div class="line-break"></div>
            <table width="100%">
                <tr>
                    <td class="left">
                        <p><strong>Date:</strong> @DateIndo($ticketData->date)</p>
                        <p><strong>Name:</strong> {{$custData->customer_first_name}}</p>
                    </td>
                    <td class="right">
                        <p><strong>Ticket Code:</strong> {{$orderedTicketData->ordered_number}}</p>
                        <p><strong>Ticket Category:</strong> {{$ticketData->ticket_name}}</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="qr-code">
            <h3>Scan this QR Code to Access the Event</h3>
            <img src="data:image/svg+xml;base64,{!! base64_encode(SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->generate($orderedTicketData->ordered_number)) !!}" alt="QR Code">

            <h3>Ticket 1 Dari 1</h3>
        </div>

        <div class="footer">
            <p>Thank you for purchasing your ticket! We look forward to seeing you at the event.</p>
        </div>
    </div>

</body>
</html>
