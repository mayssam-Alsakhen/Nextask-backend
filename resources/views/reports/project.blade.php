<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Project Report</title>
  <style>
    body { font-family: sans-serif; font-size: 12px; }
    h1 { text-align: center; font-size: 18px; margin-bottom: 0.5em; }
    .summary p { margin: 0.2em 0; }
    table { width:100%; border-collapse: collapse; margin-top:1em; }
    th, td { border:1px solid #444; padding:4px; }
    th { background:#eee; }
  </style>
</head>
<body>
  <h1>{{ $project['name'] }}</h1>
  <div class="summary">
    <p><strong>Description:</strong> {{ $project['description'] }}</p>
    <p><strong>Due Date:</strong> {{ $project['due_date'] }}</p>
    <p><strong>Status:</strong> {{ $project['status'] }}</p>
    <p><strong>Progress:</strong> {{ $project['progress'] }}</p>
  </div>

  <h2>Tasks</h2>
  <table>
    <thead>
      <tr>
        <th>Title</th><th>Status</th><th>Due Date</th>
        <th>Important</th><th>Progress</th><th>Assigned</th>
      </tr>
    </thead>
    <tbody>
      @foreach($tasks as $t)
      <tr>
        <td>{{ $t['title'] }}</td>
        <td>{{ $t['status'] }}</td>
        <td>{{ $t['due_date'] }}</td>
        <td>{{ $t['isImportant'] }}</td>
        <td>{{ $t['progress'] }}</td>
        <td>{{ $t['assigned'] }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
