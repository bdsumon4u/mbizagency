<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cyber 32 Agency - Dollar Support</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    * {margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif;}
    body {background:#f8fafc; color:#0f172a;}

    header {
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding:20px 50px;
      background:#ffffff;
      box-shadow:0 2px 10px rgba(0,0,0,0.05);
    }

    header h1 {color:#dc2626;}
    nav a {
      color:#0f172a;
      margin-left:20px;
      text-decoration:none;
      font-weight:500;
    }

    nav a:hover {
      color:#16a34a;
    }

    .hero {
      text-align:center;
      padding:100px 20px;
      background:linear-gradient(135deg,#fee2e2,#dcfce7);
    }

    .hero h2 {
      font-size:40px;
      margin-bottom:20px;
    }

    .hero p {
      font-size:18px;
      margin-bottom:30px;
      color:#475569;
    }

    .btn {
      padding:12px 25px;
      background:#dc2626;
      border:none;
      border-radius:6px;
      color:#fff;
      font-weight:600;
      cursor:pointer;
      text-decoration:none;
    }

    .btn:hover {
      background:#16a34a;
    }

    .services {
      padding:60px 50px;
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
      gap:20px;
    }

    .card {
      background:#ffffff;
      padding:25px;
      border-radius:10px;
      text-align:center;
      transition:0.3s;
      box-shadow:0 5px 15px rgba(0,0,0,0.05);
      border-top:4px solid #dc2626;
    }

    .card:hover {
      transform:translateY(-5px);
      border-top:4px solid #16a34a;
    }

    .card h3 {
      margin-bottom:10px;
      color:#dc2626;
    }

    .about {
      padding:60px 50px;
      text-align:center;
      background:#ffffff;
    }

    .about h2 {
      color:#16a34a;
      margin-bottom:15px;
    }

    .footer {
      text-align:center;
      padding:20px;
      background:linear-gradient(135deg,#fee2e2,#dcfce7);
      margin-top:40px;
      color:#065f46;
    }
  </style>
</head>
<body>

<header>
  <h1>Cyber 32</h1>
  <nav>
    <a href="#">হোম</a>
    <a href="#services">সার্ভিস</a>
    <a href="#about">আমাদের সম্পর্কে</a>
    <a href="#contact">যোগাযোগ</a>
  </nav>
</header>

<section class="hero">
  <h2>Meta Authorized Ad Account ও ডলার সাপোর্ট</h2>
  <p>আমরা শুধুমাত্র Meta Authorized Ad Account প্রদান করি এবং সেই অ্যাকাউন্টে নিরাপদভাবে ডলার সাপোর্ট দিয়ে থাকি</p>
  <a href="{{ route('filament.app.auth.login') }}" class="btn">কাস্টমার লগইন</a>
</section>

<section id="services" class="services">
  <div class="card">
    <h3>Meta Authorized Ad Account</h3>
    <p>বিশ্বস্ত ও ভেরিফাইড Meta ad account প্রদান করা হয়</p>
  </div>

  <div class="card">
    <h3>Ad Account Dollar Support</h3>
    <p>আপনার ad account-এ দ্রুত এবং নিরাপদভাবে ডলার প্রদান</p>
  </div>

  <div class="card">
    <h3>Secure Transaction</h3>
    <p>প্রতিটি লেনদেনে ইনভয়েস ও সম্পূর্ণ ট্রান্সপারেন্সি নিশ্চিত</p>
  </div>

  <div class="card">
    <h3>Dedicated Support</h3>
    <p>আপনার ad campaign এর জন্য সার্বক্ষণিক সাপোর্ট</p>
  </div>
</section>

<section id="about" class="about">
  <h2>Cyber 32 Agency সম্পর্কে</h2>
  <p>Cyber 32 Agency শুধুমাত্র Meta Authorized Ad Account এবং সেই অ্যাকাউন্টে ডলার সাপোর্ট প্রদান করে। আমরা নিরাপদ ট্রানজেকশন, স্বচ্ছতা এবং ক্লায়েন্টের সম্পূর্ণ বিশ্বাস নিশ্চিত করি।</p>
</section>

<div class="footer">
  <p>© ২০২৬ Cyber 32 Agency. সর্বস্বত্ব সংরক্ষিত।</p>
</div>

</body>
</html>
