<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#38040e">
  <meta name="description" content="Your local connection to homes, workspaces and PG stays in Pune. Explore Pehchanvala's personal approach to real estate.">
  <title>Pehchanvala — A place. A connection. A possibility.</title>
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="preconnect" href="https://images.unsplash.com">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;450;500;550;600;650;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>
  <a class="skip" href="#main">Skip to content</a>
  <div class="topline"><span>PUNE, THROUGH PEOPLE WHO KNOW IT.</span><a href="tel:+918600107440">Let’s talk <span>+91 86001 07440 ↗</span></a></div>
  <header class="header" id="header">
    <a href="#/" class="brand" aria-label="Pehchanvala home"><img src="/assets/logo.png" alt="Pehchanvala — People, Places, Possibilities" width="280" height="92"></a>
    <nav id="navigation" aria-label="Main navigation">
      <a href="#/" data-nav="home">Home</a><a href="#/properties" data-nav="properties">Our properties</a><a href="#/about" data-nav="about">The agency</a><a href="#/journal" data-nav="journal">Journal</a>
    </nav>
    <a class="button header-cta" href="#/contact">Find your connection <span>↗</span></a>
    <button class="menu-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="navigation"><span></span><span></span></button>
  </header>
  <main id="main" tabindex="-1"></main>
  <footer class="footer">
    <div class="footer-top"><p>Good places start with<br><em>the right connection.</em></p><a href="#/contact" class="circle-link" aria-label="Contact Pehchanvala">↗</a></div>
    <div class="footer-grid"><div><a href="#/" class="footer-logo"><img src="/assets/logo-white.png" alt="Pehchanvala" width="300" height="98" loading="lazy"></a><p>Your local connection to Pune.<br>People. Places. Possibilities.</p></div><div><span class="eyebrow">EXPLORE</span><a href="#/properties">Our properties</a><a href="#/about">The agency</a><a href="#/journal">Journal</a><a href="#/list-property">List your property</a></div><div><span class="eyebrow">LET’S CONNECT</span><a href="tel:+918600107440">+91 86001 07440</a><a href="mailto:info@pehchanvala.com">info@pehchanvala.com</a><a href="https://www.instagram.com/pehchanvala/" target="_blank" rel="noopener">Instagram ↗</a><a href="#/payment">Make a payment ↗</a></div><div><span class="eyebrow">BASED IN PUNE</span><p>Local understanding.<br>Personal attention.<br>A little less searching.<br>A lot more belonging.</p></div></div>
    <div class="footer-bottom"><span>© <span id="year"></span> Pehchanvala</span><div><a href="#/privacy">Privacy</a><a href="#/terms">Terms & conditions</a><a href="#/refund">Refund policy</a><a href="/admin">Agency login</a></div></div>
    <p class="preview-note">@if($properties->contains(fn($p) => $p['illustrative'])) Includes illustrative property content. @endif Confirm property prices and availability directly with the agency.</p>
  </footer>
  <dialog id="sheet" class="sheet" aria-labelledby="sheet-title"><div class="sheet-handle"></div><header class="sheet-header"><h2 id="sheet-title"></h2><button class="icon-button" data-close-sheet aria-label="Close panel">✕</button></header><div id="sheet-content"></div></dialog>
  <div id="toast" class="toast" role="status" aria-live="polite"></div>
  <script>window.PEHCHANVALA_CONFIG = {{ Illuminate\Support\Js::from($publicConfig) }}; window.PEHCHANVALA_DATA = {{ Illuminate\Support\Js::from(['properties'=>$properties,'posts'=>$posts,'policies'=>$policies]) }};</script>
</body>
</html>
