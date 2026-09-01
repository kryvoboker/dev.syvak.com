<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $app_name = trim(str_replace('(' . config('app.env') . ')', '', config('app.name')));
    @endphp

    <title>Помилка 500 — {{ $app_name }}</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#101812">

    <style>
        :root {
            --page-bg: #101812;
            --page-bg-deep: #0c120e;
            --text: #f3f2ee;
            --muted: rgba(243, 242, 238, .48);
            --muted-strong: rgba(243, 242, 238, .68);
            --line: rgba(243, 242, 238, .14);
            --line-strong: rgba(243, 242, 238, .28);
            --paper: #f3f0ed;
            --ink: #121613;
        }

        * {
            box-sizing: border-box;
        }

        html {
            background: var(--page-bg);
        }

        body {
            min-width: 20rem;
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at 72% 26%, rgba(85, 34, 31, .16), transparent 28%),
                linear-gradient(180deg, var(--page-bg-deep) 0%, var(--page-bg) 20%, var(--page-bg) 100%);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            font-size: .875rem;
            font-weight: 400;
        }

        a {
            color: inherit;
        }

        .page {
            min-height: 100svh;
            display: flex;
            flex-direction: column;
        }

        .container {
            width: min(110rem, calc(100% - 7rem));
            margin: 0 auto;
        }

        .header {
            border-bottom: .0625rem solid var(--line);
        }

        .header__inner {
            min-height: 5.75rem;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 2rem;
        }

        .brand {
            display: inline-flex;
            width: max-content;
            align-items: center;
            color: var(--text);
            text-decoration: none;
            font-size: 1.5625rem;
            font-weight: 700;
            letter-spacing: .42em;
            line-height: 1;
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2.25rem;
            font-size: .75rem;
            text-transform: uppercase;
        }

        .nav a {
            text-decoration: none;
            transition: opacity .2s ease;
        }

        .nav a:hover {
            opacity: .62;
        }

        .header__actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
        }

        .language {
            font-size: .875rem;
            text-decoration: none;
        }

        .icon-link {
            width: 1.375rem;
            height: 1.375rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .search-icon {
            position: relative;
            width: 1rem;
            height: 1rem;
            border: .0625rem solid var(--text);
            border-radius: 50%;
        }

        .search-icon::after {
            content: '';
            position: absolute;
            width: .4375rem;
            height: .0625rem;
            right: -.3125rem;
            bottom: -.125rem;
            background: var(--text);
            transform: rotate(45deg);
            transform-origin: left center;
        }

        .bag-icon {
            position: relative;
            width: .9375rem;
            height: .9375rem;
            margin-top: .25rem;
            border: .0625rem solid var(--text);
            border-radius: .0625rem;
        }

        .bag-icon::before {
            content: '';
            position: absolute;
            width: .4375rem;
            height: .3125rem;
            top: -.375rem;
            left: .1875rem;
            border: .0625rem solid var(--text);
            border-bottom: 0;
            border-radius: .5rem .5rem 0 0;
        }

        .main {
            flex: 1 0 auto;
        }

        .error {
            min-height: 38.75rem;
            display: grid;
            grid-template-columns: minmax(0, .8fr) minmax(22.5rem, 1fr);
            align-items: center;
            gap: 7vw;
            padding: 5.75rem 0 6.875rem;
        }

        .error__visual {
            position: relative;
            min-height: 24.375rem;
            display: flex;
            align-items: center;
            border-top: .0625rem solid var(--line);
            border-bottom: .0625rem solid var(--line);
            overflow: hidden;
        }

        .error__code {
            width: 100%;
            text-align: center;
            margin: 0;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(9.375rem, 17vw, 20.625rem);
            font-weight: 400;
            line-height: .8;
            white-space: nowrap;
        }

        .error__content {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .eyebrow {
            margin: 0 0 1.375rem;
            color: var(--muted);
            font-size: .6875rem;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .error__title {
            margin: 0;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(2.625rem, 4.4vw, 4.875rem);
            font-weight: 400;
            line-height: .96;
            text-transform: uppercase;
            text-align: center;
        }

        .error__text {
            max-width: 31.25rem;
            margin: 1.75rem 0 0;
            color: var(--muted-strong);
            font-size: .9375rem;
            line-height: 1.55;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            margin-top: 2.375rem;
        }

        .button {
            min-width: 11.875rem;
            min-height: 2.625rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .625rem 1.5rem;
            border: .0625rem solid var(--line-strong);
            background: transparent;
            color: var(--text);
            text-decoration: none;
            font-size: .75rem;
            line-height: 1;
            text-transform: uppercase;
            cursor: pointer;
            transition: background .2s ease, color .2s ease, border-color .2s ease;
        }

        .button--primary {
            border-color: var(--paper);
            background: var(--paper);
            color: var(--ink);
        }

        .button:hover {
            border-color: var(--paper);
            background: var(--paper);
            color: var(--ink);
        }

        .button--primary:hover {
            background: transparent;
            color: var(--text);
        }

        .error__note {
            display: flex;
            align-items: center;
            gap: .875rem;
            margin-top: 2.125rem;
            color: var(--muted);
            font-size: .6875rem;
            text-transform: uppercase;
        }

        .error__note::before {
            content: '';
            width: 2.625rem;
            height: .0625rem;
            background: var(--line-strong);
        }

        .footer {
            margin-top: auto;
            padding: 2.125rem 0 1.75rem;
        }

        .footer__grid {
            display: grid;
            grid-template-columns: 1.05fr .9fr .78fr 1fr;
            gap: clamp(2.25rem, 7vw, 8.125rem);
            padding-top: 1.625rem;
        }

        .footer__label {
            margin: 0 0 .875rem;
            color: var(--muted);
            font-size: .625rem;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .telegram {
            width: min(100%, 17.1875rem);
            min-height: 2.375rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            padding: 0 .75rem;
            background: var(--paper);
            color: var(--ink);
            text-decoration: none;
            font-size: .6875rem;
            text-transform: uppercase;
        }

        .telegram__arrow {
            width: 1.4375rem;
            height: 1.4375rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: .0625rem solid rgba(18, 22, 19, .55);
            font-size: .875rem;
        }

        .footer__support {
            max-width: 17.5rem;
            margin: .75rem 0 0;
            color: var(--muted-strong);
            font-size: .6875rem;
            line-height: 1.35;
            text-transform: uppercase;
        }

        .footer__phone {
            display: block;
            margin-bottom: .75rem;
            color: var(--text);
            font-size: clamp(1.25rem, 1.55vw, 1.8125rem);
            text-decoration: none;
            white-space: nowrap;
        }

        .footer__links {
            display: grid;
            gap: .5rem;
        }

        .footer__links a {
            width: max-content;
            max-width: 100%;
            color: var(--text);
            text-decoration: none;
            font-size: .6875rem;
            line-height: 1.15;
            text-transform: uppercase;
        }

        .footer__links a:hover {
            text-decoration: underline;
            text-underline-offset: .1875rem;
        }

        .footer__bottom {
            min-height: 7.625rem;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 2rem;
            padding-top: 2.75rem;
        }

        .footer__brand {
            font-size: clamp(2.125rem, 3.6vw, 3.875rem);
            letter-spacing: .36em;
        }

        .socials {
            display: flex;
            align-items: center;
            gap: 1.375rem;
            padding-bottom: .3125rem;
        }

        .socials a {
            width: 1.5rem;
            height: 1.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: .0625rem solid var(--muted-strong);
            border-radius: 50%;
            color: var(--text);
            text-decoration: none;
            font-size: .6875rem;
        }

        @media (max-width: 68.75rem) {
            .container {
                width: min(100% - 3.5rem, 110rem);
            }

            .nav {
                gap: 1.375rem;
            }

            .error {
                grid-template-columns: 1fr;
                gap: 3.5rem;
                padding: 4.625rem 0 5.5rem;
            }

            .error__visual {
                min-height: 18.75rem;
            }

            .footer__grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 47.5rem) {
            .container {
                width: calc(100% - 2.25rem);
            }

            .header__inner {
                min-height: 4.625rem;
                grid-template-columns: 1fr auto;
                gap: 1.125rem;
            }

            .brand {
                font-size: 1.25rem;
            }

            .nav {
                display: none;
            }

            .header__actions {
                gap: .75rem;
            }

            .error {
                min-height: auto;
                gap: 2.625rem;
                padding: 3.25rem 0 4.375rem;
            }

            .error__visual {
                min-height: 13.125rem;
            }

            .error__code {
                font-size: clamp(7rem, 38vw, 12.5rem);
            }

            .error__title {
                font-size: clamp(2.375rem, 12vw, 3.625rem);
            }

            .error__text {
                font-size: .875rem;
            }

            .actions {
                display: grid;
                grid-template-columns: 1fr;
            }

            .button {
                width: 100%;
            }

            .footer {
                padding-top: 1rem;
            }

            .footer__grid {
                grid-template-columns: 1fr;
                gap: 2.125rem;
            }

            .footer__bottom {
                min-height: 6.25rem;
                align-items: flex-end;
                padding-top: 3rem;
            }

            .footer__brand {
                font-size: 2.0625rem;
            }

            .socials {
                gap: .75rem;
            }
        }

        @media (max-width: 26.25rem) {
            .language {
                display: none;
            }

            .footer__bottom {
                align-items: flex-start;
                flex-direction: column;
                gap: 1.75rem;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <header class="header">
        <div class="container header__inner">
            <a class="brand" href="{{ url('/') }}" aria-label="{{ $app_name }} — головна">
                SYVAK
            </a>

            <nav class="nav" aria-label="Головна навігація">
                <a href="{{ url('/') }}">Каталог</a>
                <a href="{{ url('/') }}">Худі</a>
                <a href="{{ url('/') }}">Ексклюзивні подарунки</a>
            </nav>

            <div class="header__actions">
                <span class="language">UA</span>
                <a class="icon-link" href="{{ url('/') }}" aria-label="Пошук">
                    <span class="search-icon" aria-hidden="true"></span>
                </a>
                <a class="icon-link" href="{{ url('/') }}" aria-label="Кошик">
                    <span class="bag-icon" aria-hidden="true"></span>
                </a>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container error">
            <section class="error__visual" aria-hidden="true">
                <p class="error__code">500</p>
            </section>

            <section class="error__content" aria-labelledby="error-title">
                <p class="eyebrow">/ внутрішня помилка сервера /</p>

                <h1 class="error__title" id="error-title">
                    Щось пішло не так
                </h1>

                <p class="error__text">
                    Сталася внутрішня помилка сервера. Ми вже працюємо над її усуненням.
                    Спробуйте оновити сторінку або поверніться на головну.
                </p>

                <div class="actions">
                    <a class="button button--primary" href="{{ url('/') }}">
                        На головну
                    </a>

                    <a class="button" href="{{ url()->current() }}" rel="nofollow">
                        Оновити сторінку
                    </a>
                </div>

                <div class="error__note">
                    Код помилки / 500 /
                </div>
            </section>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer__grid">
                <section>
                    <p class="footer__label">/ підписка на новинки /</p>
                    <a class="telegram" href="{{ url('/') }}">
                        <span>Telegram</span>
                        <span class="telegram__arrow" aria-hidden="true">↗</span>
                    </a>
                    <p class="footer__support">
                        Твоя підтримка — це сила.<br>
                        Приєднуйся до тих, хто<br>
                        носить зі змістом.
                    </p>
                </section>

                <section>
                    <p class="footer__label">/ контакти /</p>
                    <a class="footer__phone" href="tel:+380953801173">+38 (095) 380-11-73</a>
                    <div class="footer__links">
                        <a href="{{ url('/') }}">Де нас знайти</a>
                        <a href="{{ url('/') }}">Контакти</a>
                    </div>
                </section>

                <section>
                    <p class="footer__label">/ меню /</p>
                    <div class="footer__links">
                        <a href="{{ url('/') }}">Жінки</a>
                        <a href="{{ url('/') }}">Чоловіки</a>
                        <a href="{{ url('/') }}">Діти</a>
                        <a href="{{ url('/') }}">Про нас</a>
                    </div>
                </section>

                <section>
                    <p class="footer__label">/ інформація /</p>
                    <div class="footer__links">
                        <a href="{{ url('/') }}">Політика конфіденційності</a>
                        <a href="{{ url('/') }}">Доставка та повернення</a>
                        <a href="{{ url('/') }}">Умови використання</a>
                        <a href="{{ url('/') }}">Співробітництво</a>
                        <a href="{{ url('/') }}">Договір публічної оферти</a>
                    </div>
                </section>
            </div>

            <div class="footer__bottom">
                <a class="brand footer__brand" href="{{ url('/') }}" aria-label="{{ $app_name }} — головна">
                    SYVAK
                </a>

                <div class="socials" aria-label="Соціальні мережі">
                    <a href="{{ url('/') }}" aria-label="Facebook">f</a>
                    <a href="{{ url('/') }}" aria-label="Instagram">◎</a>
                    <a href="{{ url('/') }}" aria-label="TikTok">♪</a>
                </div>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
