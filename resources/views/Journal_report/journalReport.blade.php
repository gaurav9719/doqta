<!DOCTYPE html>
<html>

<head>
    <title>Journal Report</title>
</head>

<body>
    
    <h1>Journal Report</h1>
    <h3>Your Health insights form </h3>
    <p>{{ \Carbon\Carbon::parse($report->start_date)->format('d/m/y') }} - {{ \Carbon\Carbon::parse($report->end_date)->format('d/m/y') }}</p>
    
    
    <!-- Decode the JSON string into an array -->
    
    @php
    $jsonData = json_decode($report->report, true);
    @endphp

    <!-- Display Symptoms -->
    <h2>Symptoms</h2>
    <ul>
        @foreach ($jsonData['symptoms'] as $symptom)
        <li>{{ $symptom }}</li>
        @endforeach
    </ul>

    <!-- Display Mood -->
    <h2>Mood</h2>
    <ul>
        @foreach ($jsonData['mood'] as $mood)
        <li>{{ $mood }}</li>
        @endforeach
    </ul>

    <!-- Display Pain -->
    <h2>Pain</h2>
    <ul>
        @foreach ($jsonData['pain'] as $pain)
        <li>{{ $pain }}</li>
        @endforeach
    </ul>

    <!-- Display Questions to Ask Your Doctor -->
    <h2>Questions to Ask Your Doctor</h2>
    <ul>
        @foreach ($jsonData['questions_to_ask_your_doctor'] as $question)
        <li>{{ $question }}</li>
        @endforeach
    </ul>
</body>

</html>