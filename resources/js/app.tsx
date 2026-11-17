import '../css/app.css';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp, Head } from '@inertiajs/react';
import { Layout } from './components/Layout';
import CorePages from './pages/CorePages';
import PlatformPages from './features/platform/PlatformPages';
const LearningPages=React.lazy(()=>import('./features/learning/LearningPages'));
const ContentPages=React.lazy(()=>import('./features/content/ContentPages'));

const coreScreens = ['home','dashboard','login','register','verify-email','forgot-password','reset-password','profile','security','settings','marketplace','checkout','orders','rewards','leaderboard','public-profile','admin-orders','admin-users','admin-settings'];
const learningScreens=['courses','course','lesson','playground','learning-paths','review','notebook','certificates','learning-admin','academies','my-courses','projects','labs','game','diagnostic','quiz','certificate','academy','games','challenges','code-reviews','standalone-quizzes','standalone-quiz'];
function ScreenContent({screen,data,auth,errors,english}:any){
  return <React.Suspense fallback={<div className="container route-skeleton" role="status" aria-label="Memuat halaman"><span/><span/><div><i/><i/><i/></div><span className="sr-only">Halaman sedang dimuat.</span></div>}>{screen==='platform-catalog'?<PlatformPages screen={screen} data={data}/>:coreScreens.includes(screen)?<CorePages screen={screen} data={data} auth={auth} errors={errors} english={english}/>:learningScreens.includes(screen)?<LearningPages screen={screen} data={data} auth={auth}/>:<ContentPages screen={screen} data={data} auth={auth}/>}</React.Suspense>;
}
function App(props: any) {
  const { screen = 'home', data = {}, auth = { user: null }, flash = {}, errors = {} } = props;
  const title=data.title || data.course?.title || data.item?.title || data.academy?.name;
  const description=data.course?.summary || data.item?.summary || 'Rumah belajar interaktif untuk rasa penasaranmu. Mulai belajar coding, data, keamanan digital, dan riset lewat praktik.';
  return <><Head title={title ? `${title} — Teras Ngulik` : 'Teras Ngulik — Belajar, coba, jadi bisa'}><meta name="description" content={description}/><meta property="og:title" content={title || 'Teras Ngulik — Belajar, coba, jadi bisa'}/><meta property="og:description" content={description}/></Head><Layout screen={screen} auth={auth} data={{...data,announcement:data.announcement??props.announcement}} flash={flash}><ScreenContent screen={screen} data={data} auth={auth} errors={errors}/></Layout></>;
}
createInertiaApp({resolve: () => App,setup({ el, App: InertiaApp, props }) {createRoot(el).render(<InertiaApp {...props}/>);},progress: {color:'#2F55D4',showSpinner:false}});



