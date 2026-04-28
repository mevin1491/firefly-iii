@extends('layout.v2')
@section('content')

<style>
    /* Essential Oil Card Shared Styles */
    .oil-card {
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        height: 100%;
    }
    .oil-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 32px rgba(0,0,0,0.18) !important;
    }
    .oil-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 1rem;
    }
    .oil-badge {
        font-size: 0.72rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 20px;
    }
    .star-rating { color: #f5a623; letter-spacing: 2px; }
    .benefit-pill {
        display: inline-block;
        background: rgba(255,255,255,0.18);
        border-radius: 20px;
        padding: 3px 12px;
        font-size: 0.8rem;
        margin: 3px 2px;
    }
    .price-tag {
        font-size: 1.9rem;
        font-weight: 800;
        line-height: 1;
    }
    .price-tag sup { font-size: 1rem; vertical-align: top; margin-top: 5px; }
    .divider-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; margin: 0 8px; vertical-align: middle; }

    /* Design 1 – Lavender Serenity */
    .card-lavender { background: linear-gradient(145deg, #6b48a8 0%, #9b72d0 60%, #c4a8ef 100%); color: #fff; }
    .card-lavender .oil-icon { background: rgba(255,255,255,0.2); color: #fff; }
    .card-lavender .btn-oil { background: #fff; color: #6b48a8; border: none; font-weight: 700; border-radius: 30px; padding: 10px 28px; }
    .card-lavender .btn-oil:hover { background: #f0e8ff; color: #4a2d80; }

    /* Design 2 – Peppermint Fresh */
    .card-peppermint { background: #ffffff; border: 2px solid #d4edda !important; }
    .card-peppermint .card-header-strip { background: linear-gradient(90deg, #1a8a4a, #44c87a); padding: 20px 24px; color: #fff; }
    .card-peppermint .oil-icon { background: rgba(255,255,255,0.22); color: #fff; width: 60px; height: 60px; font-size: 1.6rem; }
    .card-peppermint .btn-primary-oil { background: #1a8a4a; color: #fff; border: none; border-radius: 8px; padding: 9px 22px; font-weight: 600; }
    .card-peppermint .btn-primary-oil:hover { background: #147038; }
    .card-peppermint .btn-outline-oil { background: transparent; color: #1a8a4a; border: 2px solid #1a8a4a; border-radius: 8px; padding: 9px 22px; font-weight: 600; }
    .card-peppermint .btn-outline-oil:hover { background: #e9f7ef; }
    .benefit-item { display: flex; align-items: center; gap: 10px; padding: 6px 0; border-bottom: 1px solid #f0f0f0; font-size: 0.9rem; }
    .benefit-item:last-child { border-bottom: none; }
    .benefit-dot { width: 10px; height: 10px; border-radius: 50%; background: #1a8a4a; flex-shrink: 0; }

    /* Design 3 – Tea Tree Pure */
    .card-teatree { background: #f8fdfc; border-top: 5px solid #00838f !important; }
    .card-teatree .usage-tag { background: #e0f4f5; color: #00838f; border-radius: 6px; padding: 5px 12px; font-size: 0.82rem; font-weight: 600; display: inline-block; margin: 3px; }
    .card-teatree .btn-teatree { background: #00838f; color: #fff; border: none; border-radius: 10px; padding: 11px 0; font-weight: 700; width: 100%; font-size: 1rem; }
    .card-teatree .btn-teatree:hover { background: #006670; }
    .card-teatree .oil-icon { background: #e0f4f5; color: #00838f; width: 80px; height: 80px; font-size: 2.2rem; }
    .purity-bar { height: 8px; background: #c8eced; border-radius: 10px; overflow: hidden; }
    .purity-fill { height: 100%; background: linear-gradient(90deg, #00838f, #4ecdc4); border-radius: 10px; }

    /* Design 4 – Eucalyptus Premium */
    .card-eucalyptus { background: linear-gradient(160deg, #0f2a38 0%, #1a4055 55%, #0d2230 100%); color: #e8f4f8; }
    .card-eucalyptus .oil-icon { background: rgba(212,175,55,0.18); color: #d4af37; border: 2px solid rgba(212,175,55,0.4); }
    .card-eucalyptus .premium-badge { background: linear-gradient(90deg, #d4af37, #f0d060); color: #1a3040; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.1em; padding: 4px 14px; border-radius: 20px; text-transform: uppercase; }
    .card-eucalyptus .divider { border-color: rgba(212,175,55,0.25); }
    .card-eucalyptus .price-tag { color: #d4af37; }
    .card-eucalyptus .btn-gold { background: linear-gradient(90deg, #c9a227, #e8c840); color: #1a3040; border: none; border-radius: 10px; padding: 12px 0; font-weight: 800; width: 100%; font-size: 1rem; letter-spacing: 0.04em; }
    .card-eucalyptus .btn-gold:hover { background: linear-gradient(90deg, #b8911f, #d4b030); }
    .card-eucalyptus .spec-row { display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid rgba(212,175,55,0.12); font-size: 0.88rem; }
    .card-eucalyptus .spec-row:last-child { border-bottom: none; }
    .card-eucalyptus .spec-label { color: rgba(232,244,248,0.55); }
    .card-eucalyptus .spec-value { color: #d4af37; font-weight: 600; }
</style>

<div class="app-content">
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold mb-1">
                    <em class="fa-solid fa-flask me-2 text-primary"></em>
                    Essential Oil Collection
                </h2>
                <p class="text-muted mb-0">Four signature blends — crafted for balance, clarity, and wellness.</p>
            </div>
        </div>

        <!-- ===== 4 DESIGN CARDS ===== -->
        <div class="row g-4">

            <!-- ============================================================
                 DESIGN 1 — Lavender Serenity (gradient vertical card)
                 ============================================================ -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card oil-card card-lavender shadow-lg">
                    <div class="card-body p-4 text-center d-flex flex-column">

                        <!-- Icon -->
                        <div class="oil-icon mx-auto">
                            <em class="fa-solid fa-droplet"></em>
                        </div>

                        <!-- Badge -->
                        <div class="mb-3">
                            <span class="oil-badge" style="background:rgba(255,255,255,0.22);">Calming &amp; Soothing</span>
                        </div>

                        <!-- Title -->
                        <h4 class="fw-bold mb-1">Lavender Serenity</h4>
                        <p class="mb-3" style="font-size:0.9rem;opacity:0.85;">
                            Pure lavender essence sourced from Provence, France — ideal for relaxation and restful sleep.
                        </p>

                        <!-- Rating -->
                        <div class="star-rating mb-3">&#9733;&#9733;&#9733;&#9733;&#9733;
                            <small style="color:rgba(255,255,255,0.7);font-size:0.8rem;"> (284)</small>
                        </div>

                        <!-- Benefits -->
                        <div class="mb-4">
                            <span class="benefit-pill">Stress Relief</span>
                            <span class="benefit-pill">Sleep Aid</span>
                            <span class="benefit-pill">Aromatherapy</span>
                        </div>

                        <!-- Spacer -->
                        <div class="flex-grow-1"></div>

                        <!-- Price + Button -->
                        <div class="mt-3">
                            <div class="price-tag mb-3">
                                <sup>$</sup>24<span style="font-size:1.1rem;font-weight:400;">.99</span>
                                <small class="ms-2" style="font-size:0.8rem;font-weight:400;opacity:0.7;text-decoration:line-through;">$31.99</small>
                            </div>
                            <button class="btn btn-oil w-100">
                                <em class="fa-solid fa-cart-plus me-2"></em>Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Design 1 -->


            <!-- ============================================================
                 DESIGN 2 — Peppermint Fresh (header strip + benefit list)
                 ============================================================ -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card oil-card card-peppermint shadow">
                    <!-- Colored header strip -->
                    <div class="card-header-strip d-flex align-items-center gap-3">
                        <div class="oil-icon mb-0 flex-shrink-0">
                            <em class="fa-solid fa-wind"></em>
                        </div>
                        <div>
                            <span class="oil-badge" style="background:rgba(255,255,255,0.2);color:#fff;">Energising</span>
                            <h5 class="fw-bold mb-0 mt-1">Peppermint Fresh</h5>
                            <div class="star-rating" style="font-size:0.85rem;">&#9733;&#9733;&#9733;&#9733;&#9734;
                                <span style="color:rgba(255,255,255,0.75);font-size:0.75rem;"> (197)</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4 d-flex flex-column">
                        <p class="text-muted mb-3" style="font-size:0.9rem;">
                            Crisp, invigorating peppermint oil to sharpen focus, ease headaches, and boost morning energy.
                        </p>

                        <!-- Benefits list -->
                        <div class="mb-4">
                            <div class="benefit-item">
                                <span class="benefit-dot"></span>
                                <span>Improves mental clarity &amp; focus</span>
                            </div>
                            <div class="benefit-item">
                                <span class="benefit-dot"></span>
                                <span>Natural headache &amp; migraine relief</span>
                            </div>
                            <div class="benefit-item">
                                <span class="benefit-dot"></span>
                                <span>Cooling effect on skin (diluted)</span>
                            </div>
                            <div class="benefit-item">
                                <span class="benefit-dot"></span>
                                <span>10 mL · 100% pure therapeutic grade</span>
                            </div>
                        </div>

                        <div class="flex-grow-1"></div>

                        <!-- Price + dual buttons -->
                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <div>
                                <div class="price-tag text-dark" style="font-size:1.6rem;">
                                    <sup style="font-size:0.85rem;">$</sup>19<span style="font-size:0.95rem;font-weight:400;">.50</span>
                                </div>
                                <small class="text-muted">Free shipping over $40</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-oil">
                                    <em class="fa-regular fa-heart"></em>
                                </button>
                                <button class="btn btn-primary-oil">
                                    <em class="fa-solid fa-cart-plus me-1"></em>Buy
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Design 2 -->


            <!-- ============================================================
                 DESIGN 3 — Tea Tree Pure (top accent bar + purity meter)
                 ============================================================ -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card oil-card card-teatree shadow"
                     x-data="{ expanded: false }">
                    <div class="card-body p-4 d-flex flex-column">

                        <!-- Icon centered -->
                        <div class="oil-icon mx-auto mb-2">
                            <em class="fa-solid fa-leaf"></em>
                        </div>

                        <!-- Title + badge inline -->
                        <div class="text-center mb-3">
                            <h4 class="fw-bold mb-1" style="color:#00838f;">Tea Tree Pure</h4>
                            <span class="oil-badge" style="background:#e0f4f5;color:#00838f;">Antibacterial · Cleansing</span>
                        </div>

                        <!-- Rating -->
                        <div class="star-rating text-center mb-3" style="font-size:0.9rem;">
                            &#9733;&#9733;&#9733;&#9733;&#9733;
                            <small class="text-muted" style="font-size:0.78rem;"> (412)</small>
                        </div>

                        <p class="text-muted mb-3" style="font-size:0.88rem;">
                            Cold-pressed tea tree oil from Australia. Naturally antibacterial, antifungal, and antiviral — a household essential.
                        </p>

                        <!-- Purity bar -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="fw-semibold" style="color:#00838f;">Purity Rating</small>
                                <small class="fw-bold" style="color:#00838f;">99.8%</small>
                            </div>
                            <div class="purity-bar">
                                <div class="purity-fill" style="width:99.8%;"></div>
                            </div>
                        </div>

                        <!-- Expandable usage section -->
                        <div class="mb-3">
                            <button class="btn btn-link p-0 text-decoration-none fw-semibold"
                                    style="color:#00838f;font-size:0.88rem;"
                                    @click="expanded = !expanded">
                                <em class="fa-solid me-1" :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></em>
                                <span x-text="expanded ? 'Hide usage tips' : 'Show usage tips'"></span>
                            </button>
                            <div x-show="expanded" x-transition class="mt-2">
                                <div>
                                    <span class="usage-tag">Diffuser</span>
                                    <span class="usage-tag">Skincare</span>
                                    <span class="usage-tag">Cleaning</span>
                                    <span class="usage-tag">Hair care</span>
                                    <span class="usage-tag">First aid</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex-grow-1"></div>

                        <!-- Price + button -->
                        <div class="d-flex align-items-center justify-content-between mt-3">
                            <div>
                                <div class="price-tag" style="color:#00838f;font-size:1.6rem;">
                                    <sup style="font-size:0.8rem;">$</sup>16<span style="font-size:0.9rem;font-weight:400;">.75</span>
                                </div>
                                <small class="text-muted">15 mL bottle</small>
                            </div>
                            <button class="btn btn-teatree" style="width:auto;padding:11px 22px;">
                                <em class="fa-solid fa-cart-plus me-2"></em>Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Design 3 -->


            <!-- ============================================================
                 DESIGN 4 — Eucalyptus Premium (dark luxury card)
                 ============================================================ -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card oil-card card-eucalyptus shadow-lg">
                    <div class="card-body p-4 d-flex flex-column">

                        <!-- Premium badge top-right -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="oil-icon mb-0" style="width:60px;height:60px;font-size:1.6rem;">
                                <em class="fa-solid fa-spa"></em>
                            </div>
                            <span class="premium-badge">Premium</span>
                        </div>

                        <!-- Title -->
                        <h4 class="fw-bold mb-1" style="color:#e8c840;">Eucalyptus Gold</h4>
                        <p style="font-size:0.88rem;color:rgba(232,244,248,0.7);" class="mb-3">
                            Reserve-grade eucalyptus oil, triple-distilled for maximum potency. Breathe deeper, think clearer.
                        </p>

                        <!-- Star rating -->
                        <div class="star-rating mb-3" style="font-size:0.9rem;">
                            &#9733;&#9733;&#9733;&#9733;&#9733;
                            <small style="color:rgba(212,175,55,0.65);font-size:0.78rem;"> (89 verified)</small>
                        </div>

                        <hr class="divider my-2">

                        <!-- Spec table -->
                        <div class="mb-4">
                            <div class="spec-row">
                                <span class="spec-label">Origin</span>
                                <span class="spec-value">Tasmania, Australia</span>
                            </div>
                            <div class="spec-row">
                                <span class="spec-label">Volume</span>
                                <span class="spec-value">30 mL</span>
                            </div>
                            <div class="spec-row">
                                <span class="spec-label">Grade</span>
                                <span class="spec-value">Therapeutic · GC/MS Tested</span>
                            </div>
                            <div class="spec-row">
                                <span class="spec-label">Certifications</span>
                                <span class="spec-value">USDA Organic · Vegan</span>
                            </div>
                        </div>

                        <div class="flex-grow-1"></div>

                        <!-- Price -->
                        <div class="text-center mb-3">
                            <div class="price-tag">
                                <sup>$</sup>48<span style="font-size:1.1rem;font-weight:400;">.00</span>
                            </div>
                            <small style="color:rgba(212,175,55,0.55);">Limited reserve batch · 50 units left</small>
                        </div>

                        <!-- Full-width gold button -->
                        <button class="btn btn-gold">
                            <em class="fa-solid fa-star me-2"></em>Add to Collection
                        </button>
                    </div>
                </div>
            </div>
            <!-- /Design 4 -->

        </div>
        <!-- /row -->

    </div>
</div>

@endsection
