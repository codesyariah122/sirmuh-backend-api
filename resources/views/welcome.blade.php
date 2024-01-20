<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>{{env('APP_NAME')}}</title>

  <!-- Fonts -->
  <script src=" https://cdn.jsdelivr.net/npm/browser-scss@1.0.3/dist/browser-scss.min.js "></script>
  <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Amatic+SC|Raleway" rel="stylesheet">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="/assets/css/style.css" />



  <style>
    body {
      font-family: 'Nunito', sans-serif;
    }
  </style>
</head>
<body>
 <header>
  <h2><a href="#">Life is a journey with Islam</a></h2>
  <nav>
    <li><a href="#">Tours</a></li>
    <li><a href="#">About</a></li>
    <li><a href="#">Contact</a></li>
  </nav>
</header>

<section class="hero">
  <div class="background-image"></div>
  <div class="hero-content-area">
    <h1>Life is a journey with Islam</h1>
    <h3>Unmissable Adventure Tours With Your Friends</h3>
    <a href="#" class="btn">Contact Us Now</a>
  </div>
</section>

<section class="destinations">
  <h3 class="title">Some of our destinations:</h3>
  <p>Tired of the beach alone? Are the plains too plain? Come along with us on one of our unusual adventures with yout friends. Here are some pictures from people who have had elevated experiences with us.</p>
  <hr>

  <ul class="grid">
    <li class="small image-1"></li>
    <li class="large image-2"></li>
    <li class="large image-3"></li>
    <li class="small image-4"></li>
  </ul>
</section>

<section class="packages">
  <h3 class="title">Tour Packages</h3>
  <p>We offer a variety of group (minimum 5 people) packages. Whether you've spent some summers together or this might be your first adventure, we've got the perfect vacation for you.</p>
  <hr>

  <ul class="grid">
    <li>
      <i class="fa fa-compass fa-4x"></i>
      <h4>Guided Trips</h4>
      <p>Looking for the complete experience? Take a tour with one of our experts. They'll show you secrets that you're likely to miss otherwise.</p>
    </li>
    <li>
      <i class="fa fa-camera-retro fa-4x"></i>
      <h4>Photo Trips</h4>
      <p>Want to experience nature's beauty without all of that annoying exercise? Take a photo tour on one of our <em>Life is a party!</em> buses.</p>
    </li>
    <li>
      <i class="fa fa-bicycle fa-4x"></i>
      <h4>Biking Trips</h4>
      <p>If bicycles are more your speed, consider taking a tour through one of our mountain or city bike paths. We'll provide the bikes, and lunch too!</p>
    </li>
    <li>
      <i class="fa fa-flag-checkered fa-4x"></i>
      <h4>Racing Trips</h4>
      <p>Got a competitive spirit? Sign up for one of our challenge-based marathons! Try to reach the summit before any other group.</p>
    </li>
  </ul>
</section>

<section class="testimonials">
  <h3 class="title">Testimonials from our adventurers:</h3>
  <hr>
  <p class="quote">Wow! This tour made me realize how much I love being outside with my friends. After going on one of these tours, I can safely say that beer pong is my favorite game all time, also the cultural programs were really interesting!</p>
  <p class="author">- Albert Herter</p>
  <p class="quote">Wow, this really blew my mind. We had so much fun at the beach, and also some hidden secrets revealed. Come on, I'm living in this city for 5 years. Amazing!</p>
  <p class="author">- Sharon Rosenberg</p>
  <p class="quote">If you want to understand your friends better, head to the mountains. I mean, seriously. It's like sitting next to a campfire and just talk in the sunset, woah. You know? It's like that.</p>
  <p class="author">- Luis Mendoza</p>
</section>

<section class="contact">
  <h3 class="title">Learn more</h3>
  <p>Want to know about our upcoming events, or come to one of our mixers? Just sign up for our mailing list. No spam from us, we promise! Except for the spam we give you to keep up your energy while you're having fun with your friends. Have a blast! We have tons of that.</p>
  <hr>
  <form>
    <input type="email" placeholder="Email">
    <a href="#" class="btn">Subscribe now</a>
  </form>
</section>

<footer>
  <p>Images courtesy of <a href="http://unsplash.com/">unsplash</a>.</p>
  <p>Why are you even reading this?! There's never anything interesting in the footer!</p>
  <ul>
    <li><a href="#"><i class="fa fa-twitter-square fa-2x"></i></a></li>
    <li><a href="#"><i class="fa fa-facebook-square fa-2x"></i></a></li>
    <li><a href="#"><i class="fa fa-snapchat-square fa-2x"></i></a></li>
  </ul>
</footer>
</body>
</html>
