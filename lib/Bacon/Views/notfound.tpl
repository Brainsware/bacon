{% block content %}
<h1>Whoops!</h1>
<p>The page you are looking for could not be found.</p>

<form method="GET" action="http://www.google.com/">
  <div>
    <input type="text" name="q" value="{{ not_found }}" />
    <input type="submit" name="submit" value="Search on Google" />
  </div>
</form>
{% endblock %}
