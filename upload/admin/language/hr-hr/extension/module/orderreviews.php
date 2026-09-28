<?php
$_['heading_title']              = 'Recenzije Narudžbi';
$_['error_permission']           = 'Upozorenje: Nemate dopuštenje za izmjenu modula Recenzije Narudžbi!';
$_['text_success']               = 'Uspjeh: Modul Recenzije Narudžbi je uspješno izmijenjen!';
$_['text_enabled']               = 'Omogućeno';
$_['text_disabled']              = 'Onemogućeno';
$_['button_cancel']              = 'Poništi';
$_['save_changes']               = 'Spremi promjene';
$_['text_default']               = 'Zadano';
$_['text_module']                = 'Moduli';
$_['text_extension']             = 'Proširenja';
$_['text_percentage']            = 'Postotak';

$_['text_fixed']                 = 'Fiksni iznos';
$_['text_nod_disc']              = 'Bez popusta';
$_['text_multilingual']          = 'Višejezične postavke:';
$_['text_expired_coupons']       = 'Ukloni istekle kupone:';
$_['text_expired_coupons_help']  = 'Ukloni sve istekle kupone stvorene iz modula.<br /><strong>NAPOMENA:</strong> Nebitno je jesu li kuponima korišteni ili ne. Svi istekli kuponi koji su generirani iz modula bit će uklonjeni.';
$_['btn_clear_expired_coupons']  = 'Očisti kupone!';
$_['text_email_type']            = 'Vrsta maila za recenziju:';
$_['text_email_type_help']       = 'Odaberite hoćete li poslati cjelovit obrazac za recenziju ili samo poveznicu do njega na vašoj web stranici. Odaberite opciju poveznice ako se obrazac za recenziju ne prikazuje dobro.';
$_['text_send_form']             = 'Pošalji obrazac';
$_['text_send_link']             = 'Pošalji poveznicu';
$_['text_cron_job']              = 'Cron posao:';
$_['text_cron_job_help']         = 'Automatski šalje e-mailove kupcima.';
$_['text_how_to_setup']          = 'Kako postaviti cron posao?';
$_['text_notification_option']   = 'Primajte obavijesti putem e-maila kada se cron izvrši.';
$_['text_bcc']                   = 'Pošalji BCC vlasniku trgovine:';
$_['text_bcc_help']              = 'Omogućavanjem ove opcije dodaje se {e_mail} kao primatelj BCC.';
$_['review_name']                = 'Postavite naziv predloška koji će se prikazati u lijevom stupcu.';
$_['text_order_status']          = 'Status narudžbe:';
$_['text_order_status_help']     = 'Definirajte status narudžbe za odabrani e-mail recenzije.';
$_['text_select_order_status']   = 'Odaberi status narudžbe';
$_['text_customer_group']        = 'Grupa kupaca:';
$_['text_customer_group_help']   = 'Odredite grupu kupaca za odabrani e-mail recenzije.';
$_['text_all_customer_groups']   = 'Sve grupe kupaca';
$_['text_message_delay']         = 'Kašnjenje poruke:';
$_['text_message_delay_help']    = 'Definirajte nakon koliko dana poslati e-mail.<br /><br /><strong>NAPOMENA: </strong>Ako postavite kašnjenje na 0, poruka će biti poslana odmah nakon promjene statusa narudžbe.</span>';
$_['text_select_orders_by']      = 'Odaberi narudžbe prema:';
$_['text_select_orders_by_help'] = 'Odaberite kako će se odabrati narudžbe.';
$_['text_date_added']            = 'Datum dodavanja';
$_['text_date_modified']         = 'Datum izmjene';
$_['text_review_type']           = 'Vrsta recenzije:';
$_['text_review_type_help']      = 'Odaberite hoće li postojati jedan obrazac za sve proizvode u kupnji ili će svaki proizvod u toj kupnji imati pojedinačni obrazac.';
$_['text_per_product']           = 'Po proizvodu';
$_['text_per_purchase']          = 'Po kupnji';
$_['text_dispaly_images']        = 'Prikaz slika:';
$_['text_dispaly_images_help']   = 'Odaberite želite li prikazati slike proizvoda u e-mailu koji će biti poslan kupcu.';
$_['text_mail_review_subject']   = 'Predmet e-maila za recenziju';
$_['text_email_preview']         = 'Pregled e-maila';
$_['text_message']               = 'Poruka:';
$_['text_review_mail_settings']  = 'Postavke e-maila za recenziju:';
$_['text_review_mail_subject']   ='Predmet:';
$_['text_email_shortcodes']      = 'Možete koristiti sljedeće kratke kodove:
                  <br />
                  <br />{first_name} - Ime
                  <br />{last_name} - Prezime
                  <br />{order_products} - Naručeni proizvodi
                  <br />{review_form} - Obrazac za recenziju
                  <br />{order_id} - ID narudžbe (opcionalno)
                  <br />{reviewmail_link} - Poveznica za online oblik e-maila';
$_['text_email_default_message'] = '
<table style="width:100%;font-family:Verdana;">
    <tbody>
        <tr>
              <td align="center">
                 <table style="width:680px;margin:0 auto;border:1px solid #f0f0f0;line-height:1.8;font-size:1em;font-family:Verdana;">
                       <tbody>
                             <tr>
                                   <td style="font-family:inherit;padding:10px;">
                                      {reviewmail_link}
                       
                                      <p><span style="font-family: inherit; font-size: 1em; line-height: 1.8;">​Pozdrav {first_name} {last_name},</span></p>
                       
                                      <p>Nedavno ste kupili {order_products} u našoj trgovini. Što mislite o proizvodima koje ste naručili?</p>
                       
                                      <p>{review_form}</p>
                       
                                      <p>Zahvaljujemo na vašem povratnom informacijom i nadamo se da ćete nas uskoro ponovno posjetiti.</p>
                       
                                      <p>Srdačan pozdrav,<br />
                                      RecenzijeNarudžbe</p>
                                      <p><a href="{catalog_link}" target="_blank"></a></p>
                                   </td>
                             </tr>
                       </tbody>
                 </table>
              </td>
        </tr>
  </tbody>
</table>';
$_['text_discount_type']             = 'Vrsta popusta:';
$_['text_discount_type_help']        = 'Ako odaberete opciju "Bez popusta", morat ćete ukloniti sljedeće kodove iz predloška e-maila: {discount_code}, {discount_value}, {total_amount} i {date_end}.';
$_['text_discount']                  = 'Popust:';
$_['text_discount_help']             = 'Unesite postotak ili vrijednost popusta.';
$_['text_total_amount']              = 'Ukupni iznos:';
$_['text_total_amount_help']         = 'Ukupni iznos koji mora biti dostignut prije nego što kupon postane valjan.';
$_['text_validity']                  = 'Valjanost popusta:';
$_['text_validity_help']             = 'Definirajte koliko dana će kôd popusta biti aktivan nakon slanja podsjetnika.';
$_['text_discount_mail_option']      = 'Status e-maila za popust:';
$_['text_discount_mail_option_help'] = 'Kupac će primiti informacije o popustu nakon što pošalje recenziju izravno na stranici s uspjehom. Ako omogućite ovu opciju, kupac će također primiti e-mail s informacijama o popustu.';
$_['text_discount_mail_settings']    = 'Postavke e-maila za popust:';
$_['text_discount_mail_subject']     = 'Predmet e-maila za popust';
$_['text_discount_mail_preview']     = 'Pregled e-maila za popust';

$_['text_discount_mail_help']        = 'Možete koristiti sljedeće kratke kodove:
                            <br />
                            <br />{first_name} - Ime
                            <br />{last_name} - Prezime
                            <br />{discount_code} - Kôd popusta
                            <br />{discount_value} - Vrijednost popusta
                            <br />{total_amount} - Ukupan iznos
                            <br />{product_discount} - Popis proizvoda
                            <br />{category_discount} - Popis kategorija
                            <br />{date_end} - Krajnji datum
                            <br />{order_id} - ID narudžbe
                            <br /><br />';

                            $_['text_discount_mail_default_message'] = '
<table style="font-family:verdana; width:100%">
  <tbody>
      <tr>
          <td>
              <table style="border:1px solid #f0f0f0; font-family:verdana; font-size:1em; line-height:1.8; margin:0 auto; width:680px">
                  <tbody>
                      <tr>
                          <td style="padding:10px;">
                              <p>Pozdrav {first_name} {last_name},<br />
                                  <br />
                              Hvala vam na vašoj recenziji!</p>
                              
                              <p>Željeli bismo vam dodijeliti poseban promotivni kôd - <strong>{discount_code}</strong> - koji vam omogućuje <strong>{discount_value} POPUSTA</strong>.&nbsp;Kôd vrijedi nakon što potrošite <strong>{total_amount}</strong>. Ova promocija je samo za vas i ističe <strong>{date_end}</strong>.</p>
                              
                              <p>Možete primijeniti popust na sve proizvode ili na sve proizvode u dolje navedenim kategorijama:</p>
                              <p>{product_discount}</p>
                <br />
                              <p>{category_discount}</p>
                              <br />
                              <pNadamo se da ćete nas ponovno posjetiti uskoro.</p>
                              
                              <p>Srdačan pozdrav,<br />
                              OrderReviews.</p>
                              
                              <p><a href="{catalog_url}" target="_blank"></a></p>
                          </td>
                      </tr>
                  </tbody>
              </table>
          </td>
      </tr>
  </tbody>
</table>';
$_['text_select_date_format']         = 'Odaberite format datuma za završni datum valjanosti kupona:';

$_['entry_category']      = 'Kategorija';
$_['entry_product']       = 'Proizvodi';
$_['help_category']       = 'Odaberite sve proizvode unutar odabrane kategorije.';
$_['help_product']        = 'Odaberite određene proizvode na koje će se kupon primijeniti. Ako ne odaberete proizvode, kupon će se primijeniti na cijelu košaricu.';
$_['text_logs']         = 'Spremi zapisnik';
$_['text_log_help']       = 'Spremi zapisnik poslanih e-pošta.';
$_['text_order_id']       = 'ID narudžbe';
$_['text_customer']       = 'Kupac';
$_['text_email']        = 'E-pošta';
$_['text_date']         = 'Datum';
$_['text_remove']       = 'Ukloni';
$_['text_auto_approve']     = 'Automatska odobrenja';
$_['text_auto_approve_help']  = 'Koristite ovu značajku ako želite automatsko odobravanje recenzija kupaca.';
$_['text_auto_approve_setting'] = 'Automatsko odobrenje ocjena';
$_['text_auto_approve_setting_help']  = 'Odaberite minimalnu ocjenu koja će automatski biti odobrena';


// Glavne kartice
$_['text_control_panel']         = 'Upravljačka ploča';
$_['text_received_reviews']      = 'Primljene recenzije';
$_['text_sent_coupons']          = 'Poslani katalozi';
$_['text_mails_log']       = 'Zapisnik poslanih e-pošta';
$_['text_tab_support']           = 'Podrška';

// Kartice recenzija
$_['text_general']               = 'Općenito';
$_['text_configuration']         = 'Konfiguracija';
$_['text_email_template']        = 'Predložak e-pošte';
$_['text_discount_settings']     = 'Postavke popusta';
$_['text_discount_email']        = 'Predložak e-pošte s popustom';

// Licenciranje
$_['text_your_license']          = 'Vaša licenca';
$_['text_please_enter_the_code'] = 'Unesite kod licence za kupnju proizvoda:';
$_['text_activate_license']      = 'Aktivirajte licencu';
$_['text_not_having_a_license']  = 'Nemate kod? Nabavite ga ovdje.';
$_['text_license_holder']        = 'Imatelj licence';
$_['text_registered_domains']    = 'Registrirani domeni';
$_['text_expires_on']            = 'Licenca istječe';
$_['text_valid_license']         = 'VALJANA LICENCA';
$_['text_manage']                = 'upravljati';
$_['text_get_support']           = 'Dobiti podršku';
$_['text_community']             = 'Zajednica';
$_['text_ask_our_community']     = 'Imamo veliku zajednicu. Slobodno ih pitajte na forumu ako imate pitanja.';
$_['text_browse_forums']         = 'Pregledaj forume';
$_['text_tickets']               = 'Ulaznice';
$_['text_open_a_ticket']         = 'Želite li komunicirati jedan na jedan s našim tehničarima? Otvorite podršku.';
$_['text_open_ticket_for_real']  = 'Otvori ulaznicu';
$_['text_pre_sale']              = 'Prije prodaje';
$_['text_pre_sale_text']         = 'Imate sjajnu ideju za svoj web dućan? Naš tim programera može je ostvariti.';
$_['text_bump_the_sales']        = 'Povećaj prodaju';

$_['text_privacy_policy']           = "Pravila privatnosti";
$_['text_privacy_policy_help']      = "Koristite ovu značajku ako želite da vaši kupci prihvate Pravila privatnosti. Kompatibilno s usklađenošću GDPR-a.";
$_['text_agree']                    = "Slažem se";


