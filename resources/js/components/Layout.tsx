import React, { useEffect, useRef, useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { Accessibility, ArrowRight, BookOpen, ChevronDown, Code2, Flame, Globe2, GraduationCap, LayoutDashboard, Menu, Moon, Search, Sparkles, Sun, Trophy, X, Bell, Mail, LogOut, Settings, ShieldCheck, LockKeyhole, HelpCircle, NotebookPen, FolderCode, Layers, CalendarDays, Award, ShoppingBag } from 'lucide-react';

export const publicMenus = [
  { label: 'Belajar', en: 'Learn', icon: BookOpen, description: 'Dari rasa penasaran jadi kemampuan.', items: [['Course','/belajar/course'],['Akademi','/belajar/akademi'],['Learning Path','/belajar/learning-path'],['Ebook','/belajar/ebook'],['Kuis Mandiri','/belajar/kuis'],['Podcast','/belajar/podcast'],['Video Pendek','/belajar/video-pendek']] },
  { label: 'Labs', en: 'Labs', icon: Code2, description: 'Tempat mencoba, salah, dan menemukan.', items: [['Coding Playground','/labs/playground'],['Coding Lab','/labs/coding-lab'],['Coding Challenge','/labs/challenge'],['Code Review','/labs/code-review'],['Game','/labs/game']] },
  { label: 'Komunitas', en: 'Community', icon: Globe2, description: 'Bertumbuh bareng teman ngulik.', items: [['Artikel','/artikel'],['Diskusi','/komunitas/diskusi'],['Project Showcase','/komunitas/showcase'],['Mentorship','/komunitas/mentorship'],['Galeri','/komunitas/galeri']] },
  { label: 'Program', en: 'Programs', icon: GraduationCap, description: 'Langkah berikutnya, bersama-sama.', items: [['Bootcamp','/program/bootcamp'],['Event','/program/event'],['Hackathon & Kompetisi','/program/kompetisi'],['Karier','/program/karier'],['Partner','/program/partner']] },
  { label: 'Marketplace', en: 'Marketplace', icon: ShoppingBag, description: 'Investasi kecil untuk ide besarmu.', items: [['Kelas Premium','/marketplace/kelas'],['Bundling','/marketplace/bundling'],['Langganan','/marketplace/langganan'],['Merchandise','/marketplace/merchandise'],['Preorder','/marketplace/preorder'],['Promo','/marketplace/promo']] },
  { label: 'Tentang', en: 'About', icon: HelpCircle, description: 'Kenalan dengan rumah belajarmu.', items: [['Tentang Kami','/tentang'],['Bantuan','/bantuan'],['Kontak','/kontak'],['Dokumentasi','/dokumentasi']] },
];

export function Brand({ compact = false }: { compact?: boolean }) {
  return <Link href="/" className="brand" aria-label="Teras Ngulik — beranda"><span className="brand-icon"><img src="/assets/logo.png" alt="" /></span>{!compact && <span>teras<span className="brand-second">ngulik<span className="brand-dot">.</span></span></span>}</Link>;
}

export function Avatar({ user, size = 40 }: any) {
  const value = String(user?.avatar_seed || user?.username || user?.name || 'ngulik');
  const seed = [...value].reduce((a, c) => a + c.charCodeAt(0), 0);
  const palette = ['#FFE7A0','#B9A7F7','#BCECD6','#FFD2BC','#DCE8FF'];
  return <span className="avatar" style={{ width: size, height: size, background: palette[seed % palette.length] }} aria-label={user?.name || 'Avatar'}><svg viewBox="0 0 48 48" width={size} height={size} aria-hidden="true"><path d="M7 48c0-12 7-18 17-18s17 6 17 18" fill="#2F55D4"/><circle cx="24" cy="21" r="12" fill="#FFE3BD"/><path d="M11 20c-1-12 6-17 13-17 10 0 14 8 12 18-3-3-6-9-7-11-4 5-11 8-18 10" fill="#22263A"/><circle cx="20" cy="22" r="1.2" fill="#22263A"/><circle cx="28" cy="22" r="1.2" fill="#22263A"/><path d="M21 27q3 3 6 0" stroke="#22263A" fill="none" strokeWidth="1.3" strokeLinecap="round"/></svg></span>;
}

function Tour({ close, english }: any) {
  const [step, setStep] = useState(0);
  const dialog=useRef<HTMLElement>(null);
  useEffect(()=>{
    const previous=document.activeElement as HTMLElement;
    dialog.current?.querySelector('button')?.focus();
    const trap=(event:KeyboardEvent)=>{
      if(event.key!=='Tab' || !dialog.current)return;
      const elements=Array.from(dialog.current.querySelectorAll<HTMLElement>('a,button,input,[tabindex="0"]'));
      const first=elements[0],last=elements[elements.length-1];
      if(event.shiftKey && document.activeElement===first){event.preventDefault();last?.focus();}
      if(!event.shiftKey && document.activeElement===last){event.preventDefault();first?.focus();}
    };
    document.addEventListener('keydown',trap);return()=>{document.removeEventListener('keydown',trap);previous?.focus();};
  },[]);
  const steps = english ? [
    ['Your curiosity belongs here.','Discover five academies, then choose a course at your own pace.','/belajar/course','Explore courses'],
    ['Practice as you learn.','Pause a coding recording, edit the code, and try your own ideas in the browser.','/labs/playground','Try the playground'],
    ['Make it your learning journey.','Save your projects and notes, track your progress, and earn certificates by completing courses.','/dashboard','Open dashboard'],
  ] : [
    ['Rasa penasaranmu punya tempat.','Jelajahi lima akademi, lalu pilih kelas sesuai minat dan ritme belajarmu.','/belajar/course','Jelajahi kelas'],
    ['Belajar itu perlu dicoba.','Jeda rekaman coding, utak-atik kodenya, lalu jalankan idemu sendiri di browser.','/labs/playground','Coba playground'],
    ['Perjalananmu, tersimpan rapi.','Simpan proyek dan catatan, lanjutkan progres belajar, dan raih sertifikat setelah lulus.','/dashboard','Buka dashboard'],
  ];
  return <div className="modal-backdrop" onClick={close}><section ref={dialog} className="tour-modal" role="dialog" aria-modal="true" aria-labelledby="tour-title" onClick={e => e.stopPropagation()}><button className="icon-button modal-close" onClick={close} aria-label="Tutup panduan"><X size={21}/></button><span className="tour-symbol"><Sparkles size={36}/></span><p className="eyebrow">SELAMAT DATANG DI TERAS NGULIK</p><h2 id="tour-title">{steps[step][0]}</h2><p>{steps[step][1]}</p><Link className="text-link" href={steps[step][2]} onClick={close}>{steps[step][3]} <ArrowRight size={16}/></Link><div className="tour-bottom"><span className="tour-dots">{steps.map((_, i) => <button key={i} className={i === step ? 'active' : ''} onClick={() => setStep(i)} aria-label={`Langkah ${i+1}`}/>)}</span><button className="btn btn-primary" onClick={() => step < 2 ? setStep(step+1) : close()}>{step < 2 ? 'Lanjut' : 'Mulai ngulik'} <ArrowRight size={16}/></button></div></section></div>;
}

function NewsletterBox() {
  const form=useForm({email:'',consent:false});
  return <div className="container newsletter-strip"><div><span className="eyebrow">SEPUCUK KABAR DARI TERAS</span><h3>Jaga rasa penasaran tetap menyala.</h3><p>Cerita belajar, ide baru, dan kabar baik. Secukupnya.</p></div><form onSubmit={e=>{e.preventDefault();form.post('/newsletter',{preserveScroll:true,onSuccess:()=>form.reset()});}}><div className="newsletter-input"><label className="sr-only" htmlFor="newsletter-email">Email newsletter</label><input id="newsletter-email" type="email" value={form.data.email} onChange={e=>form.setData('email',e.target.value)} placeholder="Alamat emailmu" required/><button className="btn btn-primary btn-small" disabled={form.processing}>Ikut baca <ArrowRight size={14}/></button></div><label className="check-field"><input type="checkbox" checked={form.data.consent} onChange={e=>form.setData('consent',e.target.checked)} required/><span>Saya setuju menerima newsletter. Bisa berhenti kapan saja.</span></label>{Object.values(form.errors).map((error,i)=><p className="form-error" key={i}>{error}</p>)}</form></div>;
}

export function Layout({ screen, auth, data, flash, children }: any) {
  const [menu, setMenu] = useState<string | null>(null);
  const [mobile, setMobile] = useState(false);
  const [dark, setDark] = useState(false);
  const [english, setEnglish] = useState(false);
  const [tour, setTour] = useState(false);
  const [tourNudge,setTourNudge]=useState(false);
  const [accessibilityOpen,setAccessibilityOpen]=useState(false);
  const [accessibility,setAccessibility]=useState({scale:1,contrast:false,motion:false});
  const [banner, setBanner] = useState(true);
  const [loading, setLoading] = useState(false);
  const mobileToggle=useRef<HTMLButtonElement>(null);
  const accessibilityToggle=useRef<HTMLButtonElement>(null);
  const lastMenuTrigger=useRef<HTMLButtonElement|null>(null);
  const user = auth?.user;
  useEffect(() => {
    setDark(localStorage.getItem('teras-theme') === 'dark');
    setEnglish(localStorage.getItem('teras-locale') === 'en');
    if(!localStorage.getItem('teras-tour-seen'))setTourNudge(true);
    try{const saved=JSON.parse(localStorage.getItem('teras-accessibility') || '{}');setAccessibility({scale:[1,1.12,1.25].includes(saved.scale)?saved.scale:1,contrast:saved.contrast===true,motion:saved.motion===true});}catch{}
    const start = router.on('start', () => {setLoading(true);setMenu(null);setMobile(false);});
    const finish = router.on('finish', () => setLoading(false));
    return () => {start();finish();};
  }, []);
  useEffect(() => {document.documentElement.dataset.theme = dark ? 'dark' : 'light'; localStorage.setItem('teras-theme', dark ? 'dark':'light');}, [dark]);
  useEffect(() => {document.documentElement.lang = english ? 'en' : 'id'; localStorage.setItem('teras-locale',english ? 'en':'id');}, [english]);
  useEffect(()=>{document.documentElement.style.setProperty('--font-scale',String(accessibility.scale));document.documentElement.dataset.contrast=accessibility.contrast?'high':'normal';document.documentElement.dataset.reducedMotion=accessibility.motion?'true':'false';localStorage.setItem('teras-accessibility',JSON.stringify(accessibility));},[accessibility]);
  useEffect(() => {
    const escape = (e: KeyboardEvent) => { if (e.key === 'Escape') {setMenu(null);setMobile(false);setTour(false);setAccessibilityOpen(false);if(mobile||menu){if(window.matchMedia('(max-width:1050px)').matches)mobileToggle.current?.focus();else lastMenuTrigger.current?.focus();}else if(accessibilityOpen)accessibilityToggle.current?.focus();} };
    document.addEventListener('keydown', escape);return () => document.removeEventListener('keydown', escape);
  }, [menu,mobile,accessibilityOpen]);
  const isAdmin = screen?.startsWith('admin') || screen === 'learning-admin' || screen === 'platform-catalog';
  const dashboardScreens = ['dashboard','profile','security','notebook','review','certificates','orders','rewards','leaderboard','projects','my-courses','my-paths','settings','notifications','my-programs'];
  const isDashboard = isAdmin || dashboardScreens.includes(screen) || (screen === 'learning-paths' && window.location.pathname.startsWith('/dashboard'));
  const isLesson = screen === 'lesson' || screen === 'playground';
  const announcement = data?.announcement;
  const annText = typeof announcement === 'string' ? announcement : announcement?.text;
  const sideItems = isAdmin ? [
    ['Ringkasan','/admin',LayoutDashboard],['Course & Materi','/admin/learning',BookOpen],['Konten & Program','/admin/content',Layers],['Katalog & Voucher','/admin/catalog',ShoppingBag],['Pesanan','/admin/orders',ShoppingBag],['Moderasi','/admin/moderation',ShieldCheck],['Newsletter','/admin/newsletter',Mail],['Pengguna','/admin/users',Globe2],['Bantuan','/admin/support',HelpCircle],['Whistleblowing','/admin/whistleblowing',LockKeyhole],['Pengaturan','/admin/settings',Settings],['Dashboard saya','/dashboard',GraduationCap],
  ] : [
    ['Ringkasan','/dashboard',LayoutDashboard],['Notifikasi','/dashboard/notifikasi',Bell],['Kelas Saya','/dashboard/kelas',BookOpen],['Learning Path','/dashboard/learning-path',Layers],['Bootcamp & Classroom','/dashboard/bootcamp',CalendarDays],['Lab & Proyek','/dashboard/proyek',FolderCode],['Catatan & Bookmark','/dashboard/catatan',NotebookPen],['Review Harian','/dashboard/review',Sparkles],['XP & Leaderboard','/dashboard/leaderboard',Trophy],['Bara & Reward','/dashboard/reward',Flame],['Sertifikat','/dashboard/sertifikat',Award],['Pesanan','/dashboard/pesanan',ShoppingBag],['Profil & Pengaturan','/dashboard/profil',Settings],
  ];
  const pathname = typeof window !== 'undefined' ? window.location.pathname : '';
  return <div className={`app-shell ${isDashboard ? 'with-dashboard':''}`} data-locale={english ? 'en':'id'}>
    <a className="skip-link" href="#main-content">Langsung ke konten</a>
    {loading && <div className="navigation-progress" role="progressbar" aria-label="Memuat halaman"/>}
    {banner && annText && !isLesson && <div className="announcement"><Sparkles size={14}/><span>{annText}</span>{announcement?.href && <Link href={announcement.href}>{english ? 'Explore':'Lihat selengkapnya'} <ArrowRight size={13}/></Link>}<button onClick={() => setBanner(false)} aria-label="Tutup pengumuman"><X size={15}/></button></div>}
    <header className="site-header"><div className="header-inner"><Brand/>
      <nav className={`public-nav ${mobile ? 'mobile-open':''}`} aria-label="Navigasi utama">{publicMenus.map(item => <div className="nav-group" key={item.label}><button className={`nav-trigger ${menu === item.label ? 'active':''}`} onClick={(event) => {lastMenuTrigger.current=event.currentTarget;setMenu(menu === item.label ? null:item.label);}} aria-expanded={menu === item.label} aria-controls={`menu-${item.label}`}>{english ? item.en:item.label} <ChevronDown size={12}/></button>{menu === item.label && <div className="mega-menu" id={`menu-${item.label}`}><div className="mega-intro"><item.icon size={28}/><h3>{english ? item.en:item.label}</h3><p>{item.description}</p><span className="mega-decoration">↗</span></div><div className="mega-links">{item.items.map(([label, href]) => <Link key={href} href={href} onClick={() => {setMenu(null);setMobile(false);}}>{label} <ArrowRight size={14}/></Link>)}</div></div>}</div>)}</nav>
      <div className="header-actions"><Link href="/cari" className="icon-button search-toggle" aria-label="Cari"><Search size={19}/></Link><button className="icon-button theme-toggle" onClick={() => setDark(!dark)} aria-label={dark ? 'Aktifkan tema terang':'Aktifkan tema gelap'}>{dark ? <Sun size={18}/>:<Moon size={18}/>}</button>{user ? <><Link href="/dashboard/notifikasi" className="icon-button notification-toggle" aria-label="Notifikasi"><Bell size={18}/></Link><Link href="/dashboard/reward" className="header-bara"><Flame size={15}/>{auth?.bara || 0}</Link><div className="user-nav"><button className="avatar-button" onClick={(event) => {lastMenuTrigger.current=event.currentTarget;setMenu(menu === 'account' ? null:'account');}} aria-expanded={menu === 'account'} aria-label="Menu akun"><Avatar user={user} size={34}/></button>{menu === 'account' && <div className="account-menu"><strong>{user.name}</strong><span className="muted">@{user.username}</span><Link href="/dashboard"><LayoutDashboard size={16}/>Dashboard</Link><Link href="/dashboard/profil"><Settings size={16}/>Profil & pengaturan</Link>{user.role === 'admin' && <Link href="/admin"><ShieldCheck size={16}/>Admin</Link>}<button onClick={() => router.post('/logout')}><LogOut size={16}/>Keluar</button></div>}</div></>:<><Link href="/login" className="login-link">{english ? 'Log in':'Masuk'}</Link><Link href="/register" className="btn btn-primary btn-small register-link">{english ? 'Join free':'Daftar gratis'} <ArrowRight size={14}/></Link></>}
      <button ref={mobileToggle} className="icon-button mobile-toggle" onClick={() => {setMobile(!mobile);setMenu(null);}} aria-label={mobile ? 'Tutup navigasi':'Buka navigasi'} aria-expanded={mobile}>{mobile ? <X/>:<Menu/>}</button></div>
    </div></header>
    {menu && <button className="menu-scrim" aria-label="Tutup menu" onClick={() => setMenu(null)}/>}
    {flash?.success && <div className="container"><div className="alert success" role="status">{flash.success}</div></div>}{flash?.error && <div className="container"><div className="alert error" role="alert">{flash.error}</div></div>}
    <div className={isDashboard ? 'dashboard-layout':''}>{isDashboard && <aside className="dashboard-sidebar"><div className="sidebar-heading">{isAdmin ? 'WORKSPACE ADMIN':'RUANG BELAJARMU'}</div>{sideItems.map(([label,href,Icon]:any) => <Link className={`sidebar-link ${pathname === href ? 'active':''}`} key={href} href={href}><Icon size={18}/><span>{label}</span></Link>)}<div className="sidebar-tip"><span className="badge butter"><Sparkles size={12}/> Sedikit demi sedikit</span><p>Rasa penasaran hari ini,<br/>kemampuan baru esok hari.</p><Link href="/belajar/course">Cari kelas baru <ArrowRight size={14}/></Link></div></aside>}
    <main id="main-content" className={isDashboard ? 'dashboard-main' : isLesson ? 'lesson-main' : ''} tabIndex={-1}>{React.isValidElement(children) ? React.cloneElement(children as React.ReactElement<any>,{english}) : children}</main></div>
    {!isLesson && !isDashboard && <footer className="site-footer"><NewsletterBox/><div className="container footer-grid"><div className="footer-brand"><Brand/><p>Rumah untuk ide, rasa penasaran,<br/>dan langkah pertamamu.</p><span className="footer-note">Dibuat untuk yang suka bertanya “gimana kalau?”</span></div><div><h3>Mulai ngulik</h3><Link href="/belajar/course">Jelajahi course</Link><Link href="/labs/playground">Coding playground</Link><Link href="/belajar/learning-path">Learning path</Link><Link href="/komunitas/diskusi">Komunitas</Link></div><div><h3>Kenalan dulu</h3><Link href="/tentang">Tentang Teras Ngulik</Link><Link href="/program/partner">Jadi partner</Link><Link href="/kontak">Hubungi kami</Link><Link href="/dokumentasi">Dokumentasi</Link></div><div><h3>Kami siap bantu</h3><Link href="/bantuan">Pusat bantuan</Link><Link href="/syarat-ketentuan">Syarat & ketentuan</Link><Link href="/kebijakan-privasi">Kebijakan privasi</Link><Link href="/whistleblowing">Whistleblowing</Link><Link href="/aksesibilitas">Aksesibilitas</Link></div></div><div className="container footer-bottom"><span>© {new Date().getFullYear()} Teras Ngulik. Selalu ada yang bisa diulik.</span><div><button onClick={() => setEnglish(!english)}><Globe2 size={14}/>{english ? 'English':'Indonesia'} <ChevronDown size={12}/></button><button onClick={() => setTour(true)}><HelpCircle size={14}/>{english ? 'Quick tour':'Panduan singkat'}</button></div></div></footer>}
    {tourNudge && !tour && !isLesson && <div className="tour-nudge"><span className="nudge-icon"><Sparkles size={17}/></span><button onClick={()=>setTour(true)}><strong>Baru mampir di teras?</strong><span>Kenalan dulu, yuk. <ArrowRight size={12}/></span></button><button className="nudge-close" aria-label="Tutup pengantar" onClick={()=>{setTourNudge(false);localStorage.setItem('teras-tour-seen','1');}}><X size={14}/></button></div>}
    {tour && <Tour english={english} close={() => {setTour(false);setTourNudge(false);localStorage.setItem('teras-tour-seen','1');}}/>}
    <button ref={accessibilityToggle} className="accessibility-toggle" aria-expanded={accessibilityOpen} aria-controls="accessibility-panel" aria-label="Pengaturan aksesibilitas" onClick={()=>setAccessibilityOpen(!accessibilityOpen)}><Accessibility size={21}/></button>
    {accessibilityOpen && <section id="accessibility-panel" className="accessibility-panel" aria-label="Pengaturan aksesibilitas"><div className="accessibility-heading"><h2>Nyaman untukmu</h2><button className="icon-button" onClick={()=>setAccessibilityOpen(false)} aria-label="Tutup aksesibilitas"><X size={18}/></button></div><p>Atur tampilan sesuai kebutuhanmu.</p><fieldset><legend>Ukuran teks</legend><div className="text-size-options">{[[1,'Standar'],[1.12,'Besar'],[1.25,'Lebih besar']].map(([scale,label])=><button key={scale} aria-pressed={accessibility.scale===scale} onClick={()=>setAccessibility({...accessibility,scale:Number(scale)})}>{label}</button>)}</div></fieldset><label className="a11y-option"><span>Kontras tinggi</span><input type="checkbox" checked={accessibility.contrast} onChange={e=>setAccessibility({...accessibility,contrast:e.target.checked})}/></label><label className="a11y-option"><span>Kurangi animasi</span><input type="checkbox" checked={accessibility.motion} onChange={e=>setAccessibility({...accessibility,motion:e.target.checked})}/></label><label className="a11y-option"><span>Tema gelap</span><input type="checkbox" checked={dark} onChange={e=>setDark(e.target.checked)}/></label><button className="text-button" onClick={()=>setAccessibility({scale:1,contrast:false,motion:false})}>Atur ulang preferensi</button><Link href="/aksesibilitas" className="text-link">Panduan aksesibilitas <ArrowRight size={13}/></Link></section>}
  </div>;
}






