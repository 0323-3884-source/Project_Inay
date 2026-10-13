(() => {
    const pairs = [
        ['Log in to the platform', 'Mag-login sa platform'],
        ['Choose your account role', 'Pumili ng tungkulin sa account'],
        ['Mother/User Portal', 'Portal ng Ina/Gumagamit'],
        ['Program Staff', 'Kawani ng Programa'],
        ['DSWD / 4Ps Staff', 'Kawani ng DSWD / 4Ps'],
        ['Email Address / Admin Username', 'Email Address / Username ng Admin'],
        ['Password', 'Hudyat (Password)'],
        ['Forgot password?', 'Nakalimutan ang password?'],
        ['Login as Mother/User', 'Mag-login bilang Ina/Gumagamit'],
        ['Login as Program Staff', 'Mag-login bilang Kawani ng Programa'],
        ['Login as DSWD / 4Ps Staff', 'Mag-login bilang Kawani ng DSWD / 4Ps'],
        ['Login as Admin', 'Mag-login bilang Admin'],
        ['Login', 'Mag-login'],
        ['No account yet? Register', 'Wala pang account? Magrehistro'],
        ['Please check your login details.', 'Pakisuri ang iyong mga detalye sa pag-login.'],
        ['Please login as Program Staff first.', 'Mag-login muna bilang Kawani ng Programa.'],
        ['Please login as Mother first.', 'Mag-login muna bilang Ina.'],
        ['Contact your administrator for a DSWD / 4Ps Staff account.', 'Makipag-ugnayan sa administrador para sa account ng Kawani ng DSWD / 4Ps.'],
        ['MNCH INNOVATIVE MATERNAL PROGRAM', 'MAKABAGONG PROGRAMA PARA SA KALUSUGAN NG INA AT ANAK'],
        ['Innovative Nanay Building Strengthening Maternal, Neonatal and Child Health Program', 'Makabagong programa ng INAY para sa pagpapalakas ng kalusugan ng ina, bagong silang, at bata'],
        ['Mother Portal', 'Portal ng Ina'],
        ['Mother', 'Ina'],
        ['Maternal & Child Health', 'Kalusugan ng Ina at Anak'],
        ['Mother Care Navigation', 'Mga Gabay sa Pangangalaga ng Ina'],
        ['Dashboard', 'Pangkalahatang Tanaw'],
        ['Maternal Monitoring', 'Pagsubaybay sa Kalusugan ng Ina'],
        ['Child Health', 'Kalusugan ng Anak'],
        ['My Appointments', 'Aking mga Appointment'],
        ['Documents', 'Mga Dokumento'],
        ['Health Services', 'Mga Serbisyong Pangkalusugan'],
        ['Messages', 'Mga Mensahe'],
        ['Logout', 'Mag-logout'],
        ['My Profile', 'Aking Profile'],
        ['Profile', 'Profile'],
        ['Settings', 'Mga Setting'],
        ['Account Settings', 'Mga Setting ng Account'],
        ['Care profile active', 'Aktibo ang profile ng pangangalaga'],
        ['Not pregnant', 'Hindi buntis'],
        ['Pregnant', 'Buntis'],
        ['Notifications', 'Mga Abiso'],
        ['Enable browser alerts', 'Paganahin ang mga abiso sa browser'],
        ['Mark all read', 'Markahang nabasa ang lahat'],
        ['Loading notifications...', 'Kinukuha ang mga abiso...'],
        ['No notifications yet.', 'Wala pang mga abiso.'],
        ['Install App', 'I-install ang App'],
        ['Your care, records, and support in one place.', 'Ang iyong pangangalaga, mga tala, at suporta sa iisang lugar.'],
        ['Stay connected to your healthcare worker', 'Manatiling nakaugnay sa iyong kawani ng kalusugan'],
        ['Review your monitoring records, explore learning materials, and message your assigned healthcare worker.', 'Suriin ang iyong mga tala, basahin ang mga aralin, at magmensahe sa iyong nakatalagang kawani ng kalusugan.'],
        ['Message Your Healthcare Worker', 'Magmensahe sa Iyong Kawani ng Kalusugan'],
        ['Monitoring Records', 'Mga Tala ng Pagsubaybay'],
        ['My Children', 'Aking mga Anak'],
        ['Unread Messages', 'Mga Mensaheng Hindi pa Nabasa'],
        ['Latest Monitoring Record', 'Pinakabagong Tala ng Pagsubaybay'],
        ['View Monitoring Records', 'Tingnan ang mga Tala ng Pagsubaybay'],
        ['No monitoring records yet', 'Wala pang mga tala ng pagsubaybay'],
        ['Your recorded checkups will appear here once your healthcare worker adds them.', 'Lilitaw dito ang iyong mga tala ng checkup kapag idinagdag na ng kawani ng kalusugan.'],
        ['Your Healthcare Workers', 'Iyong mga Kawani ng Kalusugan'],
        ['Your Healthcare Worker', 'Iyong Kawani ng Kalusugan'],
        ['The healthcare workers assigned to your casefile.', 'Ang mga kawani ng kalusugan na nakatalaga sa iyong tala ng kaso.'],
        ['Your assigned healthcare worker for care and support.', 'Ang iyong nakatalagang kawani ng kalusugan para sa pangangalaga at suporta.'],
        ['No healthcare worker assigned yet', 'Wala pang nakatalagang kawani ng kalusugan'],
        ['Your healthcare worker will appear here once your assignment is recorded.', 'Lilitaw dito ang iyong kawani ng kalusugan kapag naitala na ang pagtatalaga.'],
        ['Open Messages', 'Buksan ang mga Mensahe'],
        ['Explore Your Care Resources', 'Tuklasin ang mga Gabay sa Pangangalaga'],
        ['Browse learning materials for your pregnancy journey.', 'Basahin ang mga aralin para sa iyong pagbubuntis.'],
        ["View your children's growth and vaccination records.", 'Tingnan ang mga tala ng paglaki at bakuna ng iyong mga anak.'],
        ['Find available health facilities and services.', 'Hanapin ang mga pasilidad at serbisyong pangkalusugan.'],
        ['View your recorded checkups →', 'Tingnan ang iyong mga naitalang checkup →'],
        ['Open child health records →', 'Buksan ang mga tala ng kalusugan ng anak →'],
        ['Open your conversations →', 'Buksan ang iyong mga pag-uusap →'],
        ['Explore materials →', 'Tuklasin ang mga aralin →'],
        ['View child records →', 'Tingnan ang mga tala ng anak →'],
        ['Browse services →', 'Tingnan ang mga serbisyo →'],
        ['Maternal Vitals Overview', 'Pangkalahatang Kalagayan ng Kalusugan ng Ina'],
        ['Review weight and blood pressure records updated by Program Staff.', 'Suriin ang mga tala ng timbang at presyon ng dugo na na-update ng Kawani ng Programa.'],
        ['Synced monitoring', 'Naka-sync na pagsubaybay'],
        ['View Only — updated by Program Staff', 'Para sa Pagtingin Lamang — ina-update ng Kawani ng Programa'],
        ['Only Program Staff can add or edit your vital signs and maternal monitoring records, including blood pressure, weight, and other checkup results. You can view your records here.', 'Kawani ng Programa lamang ang maaaring magdagdag o magbago ng iyong mga tala ng kalusugan, kabilang ang presyon ng dugo, timbang, at iba pang resulta ng checkup. Maaari mong tingnan ang iyong mga tala rito.'],
        ['Message your healthcare worker if a record needs updating', 'Magmensahe sa iyong kawani ng kalusugan kung kailangang baguhin ang isang tala'],
        ['Maternal Vital Signs', 'Mga Sukat ng Kalusugan ng Ina'],
        ['Blood Pressure', 'Presyon ng Dugo'],
        ['Blood Sugar', 'Asukal sa Dugo'],
        ['Weight', 'Timbang'],
        ['Temperature', 'Temperatura'],
        ['Heart Rate', 'Bilis ng Tibok ng Puso'],
        ['Pregnancy week at checkup', 'Linggo ng pagbubuntis noong checkup'],
        ['Measurement Date', 'Petsa ng Pagsukat'],
        ['Pregnancy Week', 'Linggo ng Pagbubuntis'],
        ['Test Type', 'Uri ng Pagsusuri'],
        ['Unit', 'Yunit'],
        ['Not applicable', 'Hindi naaangkop'],
        ['Not recorded', 'Hindi naitala'],
        ['Not provided', 'Hindi ibinigay'],
        ['Not scheduled', 'Wala pang iskedyul'],
        ['Within Reference Range', 'Nasa Saklaw ng Batayang Sukat'],
        ['Outside Reference Range', 'Labas sa Saklaw ng Batayang Sukat'],
        ['Logged', 'Naitala'],
        ['Urgent Referral Recommended', 'Inirerekomenda ang Agarang Referral'],
        ['For Review', 'Para sa Pagsusuri'],
        ['View Only', 'Para sa Pagtingin Lamang'],
        ['View Details', 'Tingnan ang mga Detalye'],
        ['Hide Details', 'Itago ang mga Detalye'],
        ['View Reference', 'Tingnan ang Sanggunian'],
        ['Weight Progress', 'Pagbabago ng Timbang'],
        ['Weight Progression', 'Pagbabago ng Timbang'],
        ['Weight readings update from recorded maternal vitals.', 'Ina-update ang timbang mula sa mga naitalang sukat ng kalusugan ng ina.'],
        ['Trend Chart', 'Tsart ng Pagbabago'],
        ['Blood Pressure Trends', 'Pagbabago ng Presyon ng Dugo'],
        ['Recent Records', 'Mga Kamakailang Tala'],
        ['Monitoring History', 'Kasaysayan ng Pagsubaybay'],
        ['Date', 'Petsa'],
        ['Recorded By', 'Nagtala'],
        ['Doctor List', 'Listahan ng mga Tagapagbigay ng Pangangalaga'],
        ['Search Doctor', 'Maghanap ng Tagapagbigay ng Pangangalaga'],
        ['Filter', 'Salain'],
        ['Clear', 'Alisin'],
        ['Filter by Barangay', 'Salain ayon sa Barangay'],
        ['Select a barangay to show doctors assigned nearby.', 'Pumili ng barangay upang makita ang mga tagapagbigay ng pangangalaga na nakatalaga sa malapit.'],
        ['Search barangay', 'Maghanap ng barangay'],
        ['No barangay matched your search.', 'Walang barangay na tumugma sa iyong paghahanap.'],
        ['Apply Filter', 'Ilapat ang Pagsala'],
        ['All', 'Lahat'],
        ['All Barangays', 'Lahat ng Barangay'],
        ['No Slots', 'Walang Bakanteng Oras'],
        ['Details', 'Mga Detalye'],
        ['Book Appointment', 'Mag-book ng Appointment'],
        ['Cancel Request', 'Kanselahin ang Kahilingan'],
        ['Cancel Appointment', 'Kanselahin ang Appointment'],
        ['Appointment History', 'Kasaysayan ng mga Appointment'],
        ['No doctors matched your filters.', 'Walang tagapagbigay ng pangangalaga na tumugma sa iyong pagsala.'],
        ['Try another name, specialty, barangay, or category.', 'Subukan ang ibang pangalan, espesyalisasyon, barangay, o kategorya.'],
        ['Detail Doctor', 'Mga Detalye ng Tagapagbigay ng Pangangalaga'],
        ['Specialty', 'Espesyalisasyon'],
        ['Speciality', 'Espesyalisasyon'],
        ['Location', 'Lokasyon'],
        ['Experience', 'Karanasan'],
        ['Reviews', 'Mga Pagsusuri'],
        ['No verified reviews available.', 'Wala pang beripikadong pagsusuri.'],
        ['Chat', 'Makipag-usap'],
        ['Booking Appointment', 'Pag-book ng Appointment'],
        ['Choose a doctor and time slot.', 'Pumili ng tagapagbigay ng pangangalaga at oras.'],
        ['Select a date', 'Pumili ng petsa'],
        ['Available', 'May Bakante'],
        ['Selected', 'Napili'],
        ['Booked / full', 'Naka-book / puno'],
        ['Unavailable', 'Hindi Magagamit'],
        ['Patient Concerns', 'Mga Alalahanin ng Pasyente'],
        ['Describe your concerns, symptoms, or questions', 'Ilarawan ang iyong mga alalahanin, sintomas, o tanong'],
        ['Cancel', 'Kanselahin'],
        ['Confirm Appointment', 'Kumpirmahin ang Appointment'],
        ['Pending', 'Naghihintay'],
        ['Approved', 'Naaprubahan'],
        ['Completed', 'Natapos'],
        ['Cancelled', 'Nakansela'],
        ['Close chat', 'Isara ang pag-uusap'],
        ['Send', 'Ipadala'],
        ['Message', 'Mensahe'],
        ['Type a message…', 'Mag-type ng mensahe…'],
        ['Type a message...', 'Mag-type ng mensahe...'],
        ['Loading conversation…', 'Kinukuha ang pag-uusap…'],
        ['Sending…', 'Ipinapadala…'],
        ['No messages yet. Start your conversation below.', 'Wala pang mensahe. Simulan ang iyong pag-uusap sa ibaba.'],
        ['Book an appointment with this healthcare worker first to start your conversation.', 'Mag-book muna ng appointment sa kawani ng kalusugang ito upang makapagsimula ng pag-uusap.'],
        ['Chat could not be loaded. Please try again.', 'Hindi mabuksan ang pag-uusap. Pakisubukan muli.'],
        ['Chat could not be loaded. Please refresh the page and try again.', 'Hindi mabuksan ang pag-uusap. I-refresh ang pahina at subukang muli.'],
        ['Healthcare worker', 'Kawani ng kalusugan'],
        ['Online', 'Naka-online'],
        ['Offline', 'Hindi naka-online'],
        ['Search conversations', 'Maghanap ng pag-uusap'],
        ['Add Child', 'Magdagdag ng Anak'],
        ['Save Child', 'I-save ang Anak'],
        ['Child Name', 'Pangalan ng Anak'],
        ['Birth Date', 'Petsa ng Kapanganakan'],
        ['Sex', 'Kasarian'],
        ['Female', 'Babae'],
        ['Male', 'Lalaki'],
        ['Other', 'Iba'],
        ['Unspecified', 'Hindi tinukoy'],
        ['Unknown', 'Hindi alam'],
        ['Blood Type', 'Uri ng Dugo'],
        ['Profile Photo', 'Larawan sa Profile'],
        ['Vaccination Date', 'Petsa ng Pagbabakuna'],
        ['Purpose', 'Layunin'],
        ['Possible Side Effects', 'Mga Posibleng Epekto'],
        ['Growth Monitoring', 'Pagsubaybay sa Paglaki'],
        ['Vaccination Records', 'Mga Tala ng Bakuna'],
        ['Immunization', 'Pagbabakuna'],
        ['No child profile yet. Add your child profile to start pediatric monitoring.', 'Wala pang profile ng anak. Idagdag ito upang simulan ang pagsubaybay sa kalusugan ng bata.'],
        ['Upload', 'Mag-upload'],
        ['Download', 'I-download'],
        ['View', 'Tingnan'],
        ['Delete', 'Tanggalin'],
        ['Save Changes', 'I-save ang mga Pagbabago'],
        ['Search', 'Maghanap'],
        ['Health Facilities', 'Mga Pasilidad Pangkalusugan'],
        ['Emergency reminder', 'Paalala sa emerhensiya'],
        ['For severe bleeding, intense abdominal pain, seizures, fainting, or difficulty breathing, go to the nearest emergency facility immediately.', 'Kung may matinding pagdurugo, matinding pananakit ng tiyan, kombulsyon, pagkawala ng malay, o hirap sa paghinga, pumunta agad sa pinakamalapit na pasilidad para sa emerhensiya.'],
        ['Key services', 'Mga pangunahing serbisyo'],
        ['Services not specified', 'Hindi tinukoy ang mga serbisyo'],
        ['GPS needed', 'Kailangan ang GPS'],
        ['Listed', 'Nakalista'],
        ['No local listed facilities in this category.', 'Walang lokal na pasilidad na nakalista sa kategoryang ito.'],
        ['Enable GPS to sort this local list by nearest estimated distance.', 'Paganahin ang GPS upang ayusin ang listahan ayon sa tinatayang pinakamalapit na distansiya.'],
        ['Maternal indicators are synced from Program Staff monitoring records for screening support.', 'Naka-sync ang mga sukat ng kalusugan ng ina mula sa mga tala ng Kawani ng Programa bilang suporta sa paunang pagsusuri.'],
        ['No maternal monitoring record has been synced yet.', 'Wala pang naka-sync na tala ng pagsubaybay sa kalusugan ng ina.'],
        ['Project INAY provides threshold-based screening alerts for monitoring purposes only. Results must be verified and interpreted by a qualified healthcare professional. The system does not provide a medical diagnosis.', 'Nagbibigay ang Project INAY ng mga abiso batay sa itinakdang hangganan ng mga sukat para lamang sa pagsubaybay. Dapat beripikahin at ipaliwanag ang mga resulta ng isang kwalipikadong propesyonal sa kalusugan. Hindi nagbibigay ang sistema ng medikal na diagnosis.'],
        ['Mother Care', 'Pangangalaga ng Ina'],
        ['My Documents', 'Aking mga Dokumento'],
        ['Keep your checkup records, prescriptions, and receipts together.', 'Pagsama-samahin ang iyong mga tala ng checkup, reseta, at resibo.'],
        ['+ Upload document', '+ Mag-upload ng dokumento'],
        ['Upload once, find it here and in', 'Mag-upload nang isang beses at makita ito rito at sa'],
        ['. Your program staff can view the same files in both document tabs of your casefile.', '. Makikita ng iyong kawani ang parehong mga file sa dalawang tab ng dokumento sa iyong tala ng kaso.'],
        ['Upload a document', 'Mag-upload ng dokumento'],
        ['Add a clear photo or file of your record or receipt. Choose the month it belongs to so your program staff can find it easily.', 'Magdagdag ng malinaw na larawan o file ng iyong tala o resibo. Piliin ang kaukulang buwan upang madali itong mahanap ng iyong kawani.'],
        ['Please check your upload.', 'Pakisuri ang iyong ina-upload.'],
        ['Care month', 'Buwan ng Pangangalaga'],
        ['Choose a month', 'Pumili ng buwan'],
        ['Document type', 'Uri ng dokumento'],
        ['Choose a type', 'Pumili ng uri'],
        ['Prenatal Records and Receipts', 'Mga Tala at Resibo sa Pangangalaga Bago Manganak'],
        ['Checkup Records', 'Mga Tala ng Checkup'],
        ['Prescription', 'Reseta'],
        ['Receipts', 'Mga Resibo'],
        ['Certificate', 'Sertipiko'],
        ['Other Documents', 'Iba pang Dokumento'],
        ['Choose a photo or document', 'Pumili ng larawan o dokumento'],
        ['Upload and share with staff', 'I-upload at ibahagi sa kawani'],
        ['Document details', 'Mga detalye ng dokumento'],
        ['Download file', 'I-download ang file'],
        ['Close upload dialog', 'Isara ang pag-upload'],
        ['Close document preview', 'Isara ang pagtingin sa dokumento'],
        ['Select a contact', 'Pumili ng kausap'],
        ['Select a contact to start messaging.', 'Pumili ng kausap upang magsimulang magmensahe.'],
        ['Your available conversations will appear here.', 'Lilitaw dito ang iyong mga pag-uusap.'],
        ['Loading messages...', 'Kinukuha ang mga mensahe...'],
        ['Sun', 'Ling'], ['Mon', 'Lun'], ['Tue', 'Mar'], ['Wed', 'Miy'], ['Thu', 'Huw'], ['Fri', 'Biy'], ['Sat', 'Sab'],
        ['Month', 'Buwan'], ['Week', 'Linggo'],
        ['Blood pressure is below the configured pregnancy screening review threshold.', 'Ang presyon ng dugo ay mas mababa sa itinakdang hangganan para sa pagsusuri sa pagbubuntis.'],
        ['Elevated blood pressure detected. Repeat measurement and healthcare-worker assessment are recommended.', 'Natukoy ang mataas na presyon ng dugo. Inirerekomenda ang muling pagsukat at pagsusuri ng kawani ng kalusugan.'],
        ['Review weight change based on pre-pregnancy BMI and pregnancy week.', 'Suriin ang pagbabago ng timbang batay sa BMI bago magbuntis at linggo ng pagbubuntis.'],
        ['Pre-pregnancy weight logged.', 'Naitala ang timbang bago magbuntis.'],
        ['Pre-pregnancy BMI logged.', 'Naitala ang BMI bago magbuntis.'],
        ['Blood sugar value needs professional interpretation because the test type is missing or not recognized.', 'Kailangang ipaliwanag ng propesyonal ang sukat ng asukal sa dugo dahil wala o hindi kinikilala ang uri ng pagsusuri.'],
        ['Temperature is above the configured urgent referral-support threshold. Healthcare-worker assessment is recommended.', 'Ang temperatura ay lampas sa itinakdang hangganan para sa agarang referral. Inirerekomenda ang pagsusuri ng kawani ng kalusugan.'],
        ['Temperature is outside the configured review thresholds and should be assessed with symptoms and clinical context.', 'Ang temperatura ay labas sa itinakdang mga hangganan at dapat suriin kasama ng mga sintomas at kalagayang medikal.'],
        ['Temperature is within the currently configured review thresholds.', 'Ang temperatura ay nasa loob ng kasalukuyang itinakdang mga hangganan.'],
        ['Heart rate is outside the configured urgent referral-support threshold. Healthcare-worker assessment is recommended.', 'Ang bilis ng tibok ng puso ay labas sa itinakdang hangganan para sa agarang referral. Inirerekomenda ang pagsusuri ng kawani ng kalusugan.'],
        ['Heart rate is outside the configured review thresholds and should be assessed with symptoms and clinical context.', 'Ang bilis ng tibok ng puso ay labas sa itinakdang mga hangganan at dapat suriin kasama ng mga sintomas at kalagayang medikal.'],
        ['Heart rate is within the currently configured review thresholds.', 'Ang bilis ng tibok ng puso ay nasa loob ng kasalukuyang itinakdang mga hangganan.'],
        ['For Professional Interpretation', 'Para sa Pagpapaliwanag ng Propesyonal'],
        ['Mother Portal / Pediatric Care', 'Portal ng Ina / Pangangalaga ng Bata'],
        ['Child Health Monitoring', 'Pagsubaybay sa Kalusugan ng Anak'],
        ['Growth, vaccine, and follow-up status for your registered children.', 'Kalagayan ng paglaki, bakuna, at mga follow-up ng iyong mga nakarehistrong anak.'],
        ['No child profile yet', 'Wala pang profile ng anak'],
        ['Child Growth and Immunization Record', 'Tala ng Paglaki at Bakuna ng Anak'],
        ['Age', 'Edad'], ['Height', 'Tangkad'], ['Birth Weight', 'Timbang sa Kapanganakan'],
        ['Birth Length', 'Haba sa Kapanganakan'], ['Facility', 'Pasilidad'],
        ['Head / Temp', 'Ulo / Temperatura'], ['Last measurement:', 'Huling pagsukat:'],
        ['No growth measurement from Program Staff yet', 'Wala pang sukat ng paglaki mula sa Kawani ng Programa'],
        ['No active health alerts.', 'Walang aktibong abiso sa kalusugan.'],
        ['Growth History', 'Kasaysayan ng Paglaki'], ['Weight needs review', 'Kailangang suriin ang timbang'],
        ['Height needs review', 'Kailangang suriin ang tangkad'],
        ['Use My Location', 'Gamitin ang Aking Lokasyon'],
        ['Enable GPS to sort the local list by estimated distance.', 'Paganahin ang GPS upang ayusin ang lokal na listahan ayon sa tinatayang distansiya.'],
        ['Location is used only for sorting. Distance is an estimate, not travel time.', 'Ginagamit ang lokasyon para lamang sa pag-aayos ng listahan. Tantiya ang distansiya at hindi oras ng paglalakbay.'],
        ['View attachment', 'Tingnan ang kalakip'],
    ];
    const normalize = (text) => text.replace(/\s+/g, ' ').trim().toLowerCase();
    const translations = new Map();
    pairs.forEach(([en, tl]) => {
        translations.set(normalize(en), { en, tl });
        translations.set(normalize(tl), { en, tl });
    });
    const key = 'project-inay-language';
    let language = 'en';
    try { language = (localStorage.getItem(key) || localStorage.getItem('project-inay-kaalaman-language')) === 'tl' ? 'tl' : 'en'; } catch { /* Storage may be unavailable. */ }
    // User messages, names, uploaded material, and authored bilingual lessons keep their own content.
    const excluded = 'script, style, noscript, textarea, [data-inay-language-switch], [data-language-choice], [data-i18n-en], [data-message-list], [data-chat-messages], [data-conversation-list], [data-chat-name], [data-detail-name], .doctor-card-copy h2, .portal-header-name, .portal-user-name, .inay-infographic-sheet, [translate="no"]';
    const dynamicPairs = [
        [/^Welcome, (.+)\.$/, /^Maligayang pagdating, (.+)\.$/, (name) => `Welcome, ${name}.`, (name) => `Maligayang pagdating, ${name}.`],
        [/^Week (\d+) - Month (\d+)$/, /^Linggo (\d+) - Buwan (\d+)$/, (week, month) => `Week ${week} - Month ${month}`, (week, month) => `Linggo ${week} - Buwan ${month}`],
        [/^Month (\d+)$/, /^Buwan (\d+)$/, (month) => `Month ${month}`, (month) => `Buwan ${month}`],
        [/^Appointment History \((\d+)\)$/, /^Kasaysayan ng mga Appointment \((\d+)\)$/, (count) => `Appointment History (${count})`, (count) => `Kasaysayan ng mga Appointment (${count})`],
        [/^Recorded (.+)$/, /^Naitala (.+)$/, (date) => `Recorded ${date}`, (date) => `Naitala ${date}`],
        [/^(.+)'s pregnancy threshold indicators$/, /^Mga batayang sukat ng pagbubuntis ni (.+)$/, (name) => `${name}'s pregnancy threshold indicators`, (name) => `Mga batayang sukat ng pagbubuntis ni ${name}`],
        [/^(.+) is below the configured pregnancy screening review threshold\.$/, /^Ang (.+) ay mas mababa sa itinakdang hangganan para sa pagsusuri sa pagbubuntis\.$/, (test) => `${test} is below the configured pregnancy screening review threshold.`, (test) => `Ang ${test} ay mas mababa sa itinakdang hangganan para sa pagsusuri sa pagbubuntis.`],
        [/^Change from previous entry: (.+) kg\.$/, /^Pagbabago mula sa nakaraang tala: (.+) kg\.$/, (value) => `Change from previous entry: ${value} kg.`, (value) => `Pagbabago mula sa nakaraang tala: ${value} kg.`],
        [/^(\d+) active alerts?$/, /^(\d+) aktibong abiso$/, (count) => `${count} active alert${count === '1' ? '' : 's'}`, (count) => `${count} aktibong abiso`],
        [/^(\d+) vaccines completed$/, /^(\d+) bakunang natapos$/, (count) => `${count} vaccines completed`, (count) => `${count} bakunang natapos`],
        [/^(.+)'s weight needs review for age\.$/, /^Kailangang suriin ang timbang ni (.+) ayon sa edad\.$/, (name) => `${name}'s weight needs review for age.`, (name) => `Kailangang suriin ang timbang ni ${name} ayon sa edad.`],
        [/^(.+)'s height needs review for age\.$/, /^Kailangang suriin ang tangkad ni (.+) ayon sa edad\.$/, (name) => `${name}'s height needs review for age.`, (name) => `Kailangang suriin ang tangkad ni ${name} ayon sa edad.`],
    ];
    const translate = (text) => {
        const pair = translations.get(normalize(text));
        if (!pair) {
            for (const [enPattern, tlPattern, en, tl] of dynamicPairs) {
                const match = text.trim().match(enPattern) || text.trim().match(tlPattern);
                if (match) return text.replace(text.trim(), (language === 'tl' ? tl : en)(...match.slice(1)));
            }
            const sentences = text.split(/(?<=[.!?])\s+(?=[A-Z])/);
            if (sentences.length > 1) return sentences.map(translate).join(' ');
            return text;
        }
        let value = pair[language];
        const trimmed = text.trim();
        if (trimmed === trimmed.toUpperCase()) value = value.toUpperCase();
        else if (trimmed[0] === trimmed[0]?.toLowerCase()) value = value[0].toLowerCase() + value.slice(1);
        return text.replace(/\S[\s\S]*\S|\S/, value);
    };
    const applyTo = (container) => {
        if (container.nodeType === Node.TEXT_NODE) {
            if (!container.parentElement?.closest(excluded)) {
                const value = translate(container.nodeValue);
                if (value !== container.nodeValue) container.nodeValue = value;
            }
            return;
        }
        if (!(container instanceof Element) || container.closest(excluded)) return;
        const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);
        while (walker.nextNode()) applyTo(walker.currentNode);
        [container, ...container.querySelectorAll('[placeholder], [aria-label], [title]')].forEach((element) => {
            if (element.closest(excluded)) return;
            ['placeholder', 'aria-label', 'title'].forEach((attribute) => {
                if (!element.hasAttribute(attribute)) return;
                const oldValue = element.getAttribute(attribute);
                const value = translate(oldValue);
                if (value !== oldValue) element.setAttribute(attribute, value);
            });
        });
    };
    const refresh = () => {
        document.documentElement.lang = language;
        document.querySelectorAll('[data-inay-language]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.inayLanguage === language));
        });
        applyTo(document.body);
    };
    const set = (value) => {
        language = value === 'tl' ? 'tl' : 'en';
        try { localStorage.setItem(key, language); } catch { /* Keep the in-memory selection. */ }
        refresh();
        window.dispatchEvent(new CustomEvent('inay:language-change', { detail: { language } }));
    };
    window.InayLanguage = { set, get: () => language, translate };
    document.querySelectorAll('[data-inay-language]').forEach((button) => button.addEventListener('click', () => set(button.dataset.inayLanguage)));
    const observer = new MutationObserver((records) => {
        for (const record of records) {
            if (record.type === 'childList') record.addedNodes.forEach(applyTo);
            else applyTo(record.target);
        }
    });
    observer.observe(document.body, { subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: ['placeholder', 'aria-label', 'title'] });
    window.addEventListener('storage', (event) => { if (event.key === key) set(event.newValue); });
    set(language);
})();
