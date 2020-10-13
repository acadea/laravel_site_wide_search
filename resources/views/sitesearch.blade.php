<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        body{
            font-family: Arial;
        }
        ul{
            padding-left: 0;
            list-style: none;
            margin: 0;
            box-shadow: 5px 5px 15px grey ;
            max-width: 300px;
        }
        ul>li{
            padding-top: 5px;
            padding-bottom: 5px;
            padding-left: 15px;
        }
        ul>li:hover{
            background-color: #e2e8f0;

        }
    </style>
</head>
<body>

    {{--search bar--}}
    <input id="search-bar" type="text" placeholder="Search Site">

    <ul id="results">

    </ul>

    <script>

        const resultsList = document.getElementById('results');

        function createLi(searchResult){
            const li = document.createElement('li');

            const link = document.createElement('a');
            link.href = searchResult.view_link;
            link.textContent = searchResult.model;

            const h4 = document.createElement('h4')

            h4.appendChild(link);

            const span = document.createElement('span');
            span.textContent = searchResult.match;

            li.appendChild(h4);
            li.appendChild(span);

            return li;
        }

        document.getElementById('search-bar').addEventListener('input', function (event){
            event.preventDefault();

            const searched = event.target.value;

            fetch('/api/site-search?search=' + searched, {
                method: 'GET'
            }).then((response) => {
                return response.json();
            }).then((response) => {
                console.log({response})
                const results = response.data;
                // empty list
                resultsList.innerHTML = '';

                results.forEach((result) => {
                    resultsList.appendChild(createLi(result))
                })



            })

        })
    </script>

</body>
</html>
