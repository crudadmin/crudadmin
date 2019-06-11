<!DOCTYPE html>
<html>
<head>
    <title></title>

    @metatags([
        'title' => _('title meta')
    ])
</head>
<body>
    <h2>Translates</h2>
    <p>{{ _('Hello world') }}</p>
    <p>{{ _('Translate 2') }}</p>
    <p>{{ sprintf(ngettext('%d car', '%d cars', request('cars', 1)), request('cars', 1)) }}</p>
</body>
</html>