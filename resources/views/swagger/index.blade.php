<html>
<head>
    <title>{{ config('app.name') }} | Frontend API's Swagger</title>
    <link href="{{asset('swagger-ui/dist/swagger-ui.css')}}" rel="stylesheet">
</head>
<body>
<div id="swagger-ui"></div>
<script src="{{asset('swagger-ui/dist/swagger-ui-bundle.js')}}"></script>
<script type="application/javascript">
    const ui = SwaggerUIBundle({
        url: "{{ asset('api/0.0.2/openapi.yaml') }}",
        dom_id: '#swagger-ui',
    });
</script>
</body>
</html>