# Copy review

Every text people read in MTAV, as it ships, in English and Uruguayan Spanish, with where each one shows.

## Before reading the table

- **English and Spanish** come from the final files by script, so they are what ships. **"Where it shows"** is partly a script's guess at the kind of element: the place is reliable, but a kind can be off (a descriptive sentence on the Units page is labelled "button").
- **Changed:** about a quarter of the Spanish (266 of 1,101 interface strings, 173 of 421 server messages), plus the guides, FAQs and privacy page.
- **Not checked yet:** the PHP and browser tests have not run on this branch, and longer texts have not been seen on screen. The French, Italian and Portuguese guides, FAQs and privacy page were checked by script only, not read.

## Vos or tú

Mostly tú was the agreed form. This branch is written in vos as a trial, to be judged here. Where to look closest:

- The privacy page, emails and legal lines, which may read too casual.
- "Empezá vos: compartí algo." and the "Sobre vos" tab, which read oddly.
- The landing page, which mixes "tu cooperativa" with "ustedes".

Keeping tú means converting all the Spanish, including the vos strings the review fixes added on `feature/variants`.

## Texts that may promise something untrue

Both were already there, and the rewrite kept their meaning.

1. **Who sees a family's ranking.** The FAQ says "Solo tu familia. Las demás familias nunca lo ven, ni antes ni después del sorteo." Admins can download a run's full record, which appears to include every family's ranking (no real file opened yet to confirm). An admin belongs to a family too, so the FAQ would be wrong for them. If the download does include rankings, the FAQ should say admins can see them.
2. **"Ya lo estamos revisando."** Shown when the lottery cannot run because background work has stopped. Nothing near that message alerts anyone (only the code around it was searched). Remove the sentence, unless an alert is added.

## Other known problems

- **Wrong message:** when a superadmin deletes someone's account, they are told "Your account was deleted." The same key is defined twice in the language file. Fixing it needs a new key and a code change.
- **Stiff wording left in:** some standard validation messages, like "debe ser un string", because normal forms rarely trigger them.
- **Guide screenshots** still show the old wording and need retaking.

## All the texts

| English | Spanish (UY) | Where it shows |
| --- | --- | --- |
| Create an account | Crear una cuenta | Landing page: button |
| Sign in | Entrar | Landing page: button; Sign-in page |
| Everything the cooperative shares | Todo lo que la cooperativa comparte | Landing page: heading |
| From the first assembly to the keys | De la primera asamblea a la entrega de llaves | Landing page: heading |
| How it goes | Cómo funciona | Landing page: heading |
| Ready when your cooperative is | Listos para cuando tu cooperativa quiera | Landing page: heading |
| or | o | Landing page: label |
| Read the member guide | Leer la guía para cooperativistas | Landing page: link |
| Privacy | Privacidad | Landing page: link; Public pages (privacy, guide without an account) |
| Member guide | Guía para cooperativistas | Landing page: link; Public pages (privacy, guide without an account): text |
| A work day on the site | Una jornada de trabajo en la obra | Landing page: photo caption |
| Brazos Unidos, homes built in Montevideo | Brazos Unidos, viviendas construidas en Montevideo | Landing page: photo caption |
| COVISIN Fray Bentos II, a housing cooperative | COVISIN Fray Bentos II, una cooperativa de viviendas | Landing page: photo caption |
| Families building their homes, by mutual aid | Familias construyendo sus viviendas por ayuda mutua | Landing page: photo caption |
| Mesa 1, mutual-aid housing cooperatives in Montevideo | Mesa 1, cooperativas de vivienda por ayuda mutua en Montevideo | Landing page: photo caption |
| VICMAN, the first housing cooperative in Uruguay | VICMAN, la primera cooperativa de viviendas del Uruguay | Landing page: photo caption |
| Housing cooperatives in Uruguay | Cooperativas de vivienda en Uruguay | Landing page: read by screen readers |
| Pause the pictures | Pausar las fotos | Landing page: read by screen readers |
| Play the pictures | Pasar las fotos | Landing page: read by screen readers |
| carousel | carrusel | Landing page: read by screen readers, photo strip (also Gallery and files pages) |
| Language | Idioma | Landing page: read by screen readers; Your preferences page and the header's quick settings: label |
| As high in each ranking as possible; only ties are drawn at random. | Lo más arriba posible en cada orden. Solo los empates se deciden al azar. | Landing page: text |
| Assemblies and work days on one calendar; photos and documents shared with the whole cooperative. | Asambleas y jornadas de trabajo en un mismo calendario; fotos y documentos compartidos con toda la cooperativa. | Landing page: text |
| Continue with Google | Continuar con Google | Landing page: text |
| Each family joins by email, with everyone in it. | Cada familia entra por correo, con toda su gente. | Landing page: text |
| Each family joins by email, with everyone in it. Each person decides what others see of them. | Cada familia entra por correo, con toda su gente. Cada persona decide qué ven los demás de ella. | Landing page: text |
| Each family ranks its units | Cada familia ordena sus viviendas | Landing page: text |
| Each family ranks the units of its type. The lottery gives every family a unit as high in its ranking as possible. | Cada familia ordena las viviendas de su tipo. El sorteo le da a cada familia una vivienda lo más arriba posible en su orden. | Landing page: text |
| Invited by your cooperative? Open the link in your email. | ¿Te invitó tu cooperativa? Abrí el enlace que te llegó por correo. | Landing page: text |
| Its admins describe it: unit types, units, the plan. | Sus administradores lo describen: tipos de vivienda, viviendas, el plano. | Landing page: text |
| Most wanted first, together, until the lottery. | De la que más quieren a la que menos, entre todos, hasta el sorteo. | Landing page: text |
| Photos | Fotos | Landing page: text |
| The lottery gives out the units | El sorteo reparte las viviendas | Landing page: text |
| The whole cooperative in one place: its families and their people, the units and the plan of the site, assemblies and work days, and a lottery that gives every family the best home it can. | Toda la cooperativa en un solo lugar: las familias y su gente, las viviendas y el plano del terreno, las asambleas y jornadas de trabajo, y un sorteo que le da a cada familia la mejor vivienda posible. | Landing page: text |
| Invite the families | Inviten a las familias | Landing page: text; Setup checklist, admins: label |
| Start the project | Crear el proyecto | Landing page: text; Start a project page |
| A fair lottery | Un sorteo justo | Landing page: title |
| Families and their people | Las familias y su gente | Landing page: title |
| Life together | La vida en común | Landing page: title |
| The units by type, on a plan of the site you draw yourselves, floor by floor. | Las viviendas por tipo, sobre un plano del terreno que dibujan ustedes mismos, piso por piso. | Landing page: title |
| Units and the plan | Las viviendas y el plano | Landing page: title |
| Housing cooperatives, together | Cooperativas de vivienda, juntas | Landing page: title; App description when added to a phone: text |
| Create the lottery | Crear el sorteo | Open Lottery landing page: button |
| Summer house, August | Casa de verano, agosto | Open Lottery landing page: example in the lottery name field |
| Rooms | Cuartos | Open Lottery landing page: example of what is shared out |
| One per line. You can add more later. | Uno por línea. Después podés agregar más. | Open Lottery landing page: hint |
| What are you sharing out? | ¿Qué vas a repartir? | Open Lottery landing page: label |
| What is this for? | ¿Para qué es? | Open Lottery landing page: label |
| Which ones? | ¿Cuáles? | Open Lottery landing page: label |
| Name what you are sharing out and send the link. Everyone says what they prefer, and the lottery gives each person the best it can. No sign-up. | Poné qué vas a repartir y mandá el enlace. Cada uno dice qué prefiere y el sorteo le da a cada persona lo mejor que puede. Sin registrarse. | Open Lottery landing page: text |
| Share things out fairly | Repartí las cosas de forma justa | Open Lottery landing page: title |
| you | vos | An open lottery's page: beside your own name, open lottery |
| This order is fine | Este orden me sirve | An open lottery's page: button |
| Add | Sumar | An open lottery's page: button; Messages (chat) |
| Join | Sumarme | An open lottery's page: button; Your projects page: text; Find a project page: text |
| Run it like this? | ¿Lo hacemos así? | An open lottery's page: confirmation title |
| Keep it: there is no other way back to organizing this lottery from another browser. | Guardalo: no hay otra forma de volver a organizar este sorteo desde otro navegador. | An open lottery's page: hint |
| To come back from another device. Anyone with it can rank for you. | Para volver desde otro dispositivo. Quien lo tenga puede elegir por vos. | An open lottery's page: hint |
| Link to share | Enlace para compartir | An open lottery's page: label |
| Your organizer link | Tu enlace de organización | An open lottery's page: label |
| Your own link | Tu enlace | An open lottery's page: label |
| Confirm what is unusual first. | Primero confirmá lo que es fuera de lo común. | An open lottery's page: message |
| It takes at least 2 people and 1 item. | Hacen falta al menos 2 personas y 1 opción. | An open lottery's page: message |
| That name is already taken here. | Ese nombre ya está usado acá. | An open lottery's page: message |
| The lottery can't run right now. Try again in a moment. | Ahora no se puede hacer el sorteo. Probá de nuevo en un rato. | An open lottery's page: message |
| This lottery cannot hold any more. | Este sorteo no admite más. | An open lottery's page: message |
| This lottery has already run: nothing in it changes. | Este sorteo ya se hizo: no cambia nada. | An open lottery's page: message |
| This lottery is full. | Este sorteo está completo. | An open lottery's page: message |
| Ranked | Ya eligió | An open lottery's page: person already chose, open lottery |
| Not yet | Todavía no | An open lottery's page: person has not chosen yet, open lottery |
| Nothing this time | Nada esta vez | An open lottery's page: person received nothing, open lottery |
| Add one | Agregar uno | An open lottery's page: read by screen readers |
| Take {name} out | Sacar a {name} | An open lottery's page: read by screen readers |
| Your name | Tu nombre | An open lottery's page: read by screen readers |
| Copy | Copiar | An open lottery's page: read by screen readers; Plan editor, admins: text |
| Remove {name} | Quitar {name} | An open lottery's page: read by screen readers; Units page; Share files form: text |
| A lottery runs only once. | Un sorteo se hace una sola vez. | An open lottery's page: text |
| It takes at least 2 people and 1 item. | Hacen falta al menos 2 personas y 1 opción. | An open lottery's page: text |
| Kept until {date}. | Se guarda hasta el {date}. | An open lottery's page: text |
| Leave this lottery | Salir de este sorteo | An open lottery's page: text |
| Left over: {names} | Sobraron: {names} | An open lottery's page: text |
| New ones were added since you ranked. Check their place. | Se agregaron nuevos desde que elegiste. Fijate en qué lugar los querés. | An open lottery's page: text |
| Nobody sees your order until the lottery runs. | Nadie ve tu orden hasta que se haga el sorteo. | An open lottery's page: text |
| Nobody yet. Send the link. | Nadie todavía. Mandá el enlace. | An open lottery's page: text |
| Not ranked yet: {names}. They take part in an order chosen at random. | Sin elegir todavía: {names}. Participan en un orden elegido al azar. | An open lottery's page: text |
| Nothing to rank yet. | Todavía no hay nada para elegir. | An open lottery's page: text |
| Rankings become visible to everyone when the lottery runs, so that anyone can check the result. | Las preferencias se vuelven visibles para todos cuando se hace el sorteo, para que cualquiera pueda comprobar el resultado. | An open lottery's page: text |
| See the whole record | Ver el registro completo | An open lottery's page: text |
| The lottery can't run right now. Try again in a moment. | Ahora no se puede hacer el sorteo. Probá de nuevo en un rato. | An open lottery's page: text |
| The organizer took you out of this lottery. You can join again. | Quien organiza te sacó de este sorteo. Podés volver a sumarte. | An open lottery's page: text |
| {count} people will get nothing: there are too few to go round. | {count} personas se quedan sin nada: no alcanza para todos. | An open lottery's page: text |
| {count} will be left over. | Van a sobrar {count}. | An open lottery's page: text |
| Run the lottery | Hacer el sorteo | An open lottery's page: text; Setup checklist, admins: label; Lottery page: admin tools: button |
| Join in | Sumate | An open lottery's page: title |
| Organizing | Organización | An open lottery's page: title |
| The result | El resultado | An open lottery's page: title |
| Who is in | Quiénes están | An open lottery's page: title |
| Your preferences: {what} | Tus preferencias: {what} | An open lottery's page: title |
| The lottery is running. | El sorteo está en curso. | An open lottery's page: title; Lottery page: admin tools: text |
| This site is being set up. Come back soon. | Estamos preparando este sitio. Volvé pronto. | Under construction page: text |
| Under construction | En construcción | Under construction page: title |
| {app} home | Inicio de {app} | Public pages (privacy, guide without an account): read by screen readers |
| Privacy | Privacidad | Privacy page: title |
| {app} is where housing cooperatives organize their projects, up to the lottery that assigns each family its home. This page says what {app} keeps about you, who sees it and what you can ask for. It applies to this site, run by the {app} team. | {app} es donde las cooperativas de vivienda organizan sus proyectos, hasta el sorteo que adjudica a cada familia su vivienda. Esta página cuenta qué guarda {app} sobre vos, quién lo ve y qué podés pedir. Vale para este sitio, a cargo del equipo de {app}. | Privacy page: text |
| What we keep | Qué guardamos | Privacy page: heading |
| <strong>Your account:</strong> your name, email and password (stored scrambled: nobody can read it). If you add them: your phone, a few words about you, your picture and your ID number. | <strong>Tu cuenta:</strong> tu nombre, tu correo y tu contraseña (guardada cifrada: nadie puede leerla). Si los agregás: tu teléfono, unas palabras sobre vos, tu foto y tu cédula. | Privacy page: text |
| <strong>What you do in your projects:</strong> your family, your family's ranking of the units, your answers to events, the files you share, your posts in the forum, your messages in the chat and what the lottery assigned. | <strong>Lo que hacés en tus proyectos:</strong> tu familia, el orden de preferencias de tu familia, tus respuestas a los eventos, los archivos que compartís, tus mensajes en el foro y en el chat, y lo que adjudicó el sorteo. | Privacy page: text |
| <strong>Your preferences:</strong> your language and how the app looks. | <strong>Tus preferencias:</strong> tu idioma y cómo se ve la aplicación. | Privacy page: text |
| If you sign in with Google, Google tells us your name, your email and that the email is yours. We ask for nothing else, and never for your Google password. | Si entrás con Google, Google nos dice tu nombre, tu correo y que ese correo es tuyo. No pedimos nada más, y nunca tu contraseña de Google. | Privacy page: text |
| Who sees it | Quién lo ve | Privacy page: heading |
| Your messages in the chat are read only by the people in that conversation: not the project's admins, and not the platform's. | Tus mensajes en el chat los leen solo las personas de esa conversación: ni los administradores del proyecto ni los de la plataforma. | Privacy page: text |
| The people of your projects see your name and picture. Your email and phone are visible to them only if you choose so. The project's admins always see them, to reach you. | La gente de tus proyectos ve tu nombre y tu foto. Tu correo y tu teléfono los ve solo si vos lo elegís. Los administradores del proyecto los ven siempre, para poder contactarte. | Privacy page: text |
| Your ID number, if you give it, is seen only by you and your projects' admins. | Tu cédula, si la agregás, la ven solo vos y los administradores de tus proyectos. | Privacy page: text |
| Files and everything else in a project are seen only by its people, never from outside. | Los archivos y todo lo demás de un proyecto lo ve solo su gente, nunca alguien de afuera. | Privacy page: text |
| The {app} team can see what is needed to keep the site working and to help when you ask. | El equipo de {app} puede ver lo necesario para que el sitio funcione y para ayudarte cuando lo pidas. | Privacy page: text |
| We do not sell your data, show ads or share it with anyone else. Emails (invitations, confirmations, notices) are delivered through an email provider, which only carries them. | No vendemos tus datos, no mostramos publicidad ni los compartimos con nadie más. Los correos (invitaciones, confirmaciones, avisos) se mandan a través de un proveedor de correo, que solo los transporta. | Privacy page: text |
| <strong>The one exception is the Assistant.</strong> What you write in your conversation with the Assistant is sent to Anthropic, the company whose AI answers it, along with your first name, your family's name and your role in the project. Nothing about anyone else is sent. Only you read that conversation. | <strong>La única excepción es el Asistente.</strong> Lo que escribís en tu conversación con el Asistente se manda a Anthropic, la empresa cuya inteligencia artificial responde, junto con tu nombre de pila, el de tu familia y tu rol en el proyecto. No se manda nada sobre otras personas. Solo vos leés esa conversación. | Privacy page: text |
| What is recorded | Qué se registra | Privacy page: heading |
| So that everything stays checkable, {app} records what changes (what was added, changed or removed, by whom and when) and each time someone signs in. It never records what people read. When something goes wrong, it notes where, and who was using it, to fix it. Only the {app} team sees these records. | Para que todo se pueda verificar, {app} registra lo que cambia (qué se agregó, cambió o quitó, quién y cuándo) y cada vez que alguien entra. Nunca registra lo que la gente lee. Cuando algo falla, anota dónde y quién lo estaba usando, para arreglarlo. Solo el equipo de {app} ve estos registros. | Privacy page: text |
| Cookies and your device | Cookies y tu dispositivo | Privacy page: heading |
| {app} uses only the cookies it needs: one keeps you signed in, one protects your forms, and a few remember your language and appearance. There are no advertising or tracking cookies. | {app} usa solo las cookies que necesita: una te mantiene adentro, otra protege tus formularios, y algunas recuerdan tu idioma y cómo preferís ver la aplicación. No hay cookies de publicidad ni de seguimiento. | Privacy page: text |
| So that the app works without a connection, your device keeps a copy of the pages you visited. It is forgotten when someone else signs in on that device. Your device also remembers which guided tours you have already seen. | Para que la aplicación funcione sin conexión, tu dispositivo guarda una copia de las páginas que visitaste. Se olvida cuando otra persona entra en ese dispositivo. Tu dispositivo también recuerda qué recorridos guiados ya viste. | Privacy page: text |
| If you turn on notifications on a device, its browser's push service (Google's, Mozilla's or Apple's, depending on the browser) carries them to it, encrypted: it cannot read them. | Si activás las notificaciones en un dispositivo, el servicio de notificaciones de su navegador (el de Google, Mozilla o Apple, según el navegador) se las lleva cifradas: no puede leerlas. | Privacy page: text |
| How long | Por cuánto tiempo | Privacy page: heading |
| Your data is kept while you have an account. When a project ends or is deleted, its data is kept only as long as its records need it. The record of each lottery is kept, so that its result can always be checked. | Tus datos se guardan mientras tengas una cuenta. Cuando un proyecto termina o se borra, sus datos se guardan solo lo que sus registros necesiten. El registro de cada sorteo se conserva, para que su resultado siempre se pueda verificar. | Privacy page: text |
| Your rights | Tus derechos | Privacy page: heading |
| You can see and correct your data in your profile at any time, and delete your account there. You can also ask us for a copy of it, to correct or update it, or to delete your account, by writing to <a href="mailto:{contact}">{contact}</a>. We answer within the terms of Uruguay's personal data protection law (Ley N.º 18.331). You can also turn to its authority, the Unidad Reguladora y de Control de Datos Personales (URCDP). | Podés ver y corregir tus datos en tu perfil cuando quieras, y borrar tu cuenta desde ahí. También podés pedirnos una copia, corregirlos o actualizarlos, o borrar tu cuenta, escribiendo a <a href="mailto:{contact}">{contact}</a>. Respondemos en los plazos de la Ley N.º 18.331 de Protección de Datos Personales. También podés recurrir a la Unidad Reguladora y de Control de Datos Personales (URCDP). | Privacy page: text |
| Changes | Cambios | Privacy page: heading |
| If this page changes in anything that matters, we will say so in the app before it applies. | Si esta página cambia en algo importante, lo avisamos en la aplicación antes de que se aplique. | Privacy page: text |
| Email | Correo | Many pages: Sign-in page, Create an account page, Forgot password page and others |
| Password | Contraseña | Many pages: Sign-in page, Create an account page, Invitation page (from the email link) and others |
| Signing in with Google did not work. Try again, or use your email. | No se pudo entrar con Google. Probá de nuevo, o usá tu correo. | Sign-in page: Google did not work |
| Google has not confirmed that email address yet, so it cannot open an account here. | Google todavía no confirmó ese correo, así que no puede abrir una cuenta acá. | Sign-in page: Google has not confirmed the email |
| This account is blocked. | Esta cuenta está bloqueada. | Sign-in page: account blocked |
| Keep me signed in | Recordarme | Sign-in page: label |
| Forgot your password? | ¿Olvidaste tu contraseña? | Sign-in page: link; Forgot password page: confirmation title |
| Too many tries. Try again in :seconds seconds. | Demasiados intentos. Probá de nuevo en :seconds segundos. | Sign-in page: too many tries |
| That email or password is not right. Check them and try again. | Ese correo o esa contraseña no son correctos. Revisalos y probá de nuevo. | Sign-in page: wrong email or password |
| Create the account | Crear la cuenta | Create an account page: button |
| At least 8 characters | Al menos 8 caracteres | Create an account page: hint; Choose a new password page; Invitation page (from the email link); Password tab of your profile |
| Repeat the password | Repetí la contraseña | Create an account page: label; Choose a new password page; Invitation page (from the email link) |
| First name | Nombre | Many pages: Create an account page, Invitation page (from the email link), Add a family form, admins and others: label |
| Last name | Apellido | Many pages: Create an account page, Invitation page (from the email link), Add a family form, admins and others: label |
| This link to change your password is no longer valid. Ask for a new one. | Este enlace para cambiar la contraseña ya no sirve. Pedí uno nuevo. | Forgot password and new password pages: message |
| Wait a moment and try again. | Esperá un momento y probá de nuevo. | Forgot password and new password pages: message |
| We can't find an account with that email. | No encontramos ninguna cuenta con ese correo. | Forgot password and new password pages: message |
| We emailed you a link to choose a new password. | Te mandamos por correo un enlace para elegir una contraseña nueva. | Forgot password and new password pages: message |
| Your password was changed. | Tu contraseña cambió. | Forgot password and new password pages: message |
| Send the link | Enviar el enlace | Forgot password page: button |
| Back to sign in | Volver a entrar | Forgot password page: link |
| We will email you a link to choose a new one. | Te mandamos por correo un enlace para elegir una nueva. | Forgot password page: title |
| Save the password | Guardar la contraseña | Choose a new password page: button |
| New password | Contraseña nueva | Choose a new password page: label; Password tab of your profile |
| Choose a new password | Elegí una contraseña nueva | Choose a new password page: title |
| Send the link again | Enviar el enlace de nuevo | Confirm your email page: button |
| Sign out | Salir | Confirm your email page: button; The app's frame: menu and header: text |
| Confirm your email | Confirmá tu correo | Confirm your email page: title |
| We sent a link to {email}. Open it to confirm your address and continue. | Te mandamos un enlace a {email}. Abrilo para confirmar tu correo y seguir. | Confirm your email page: title |
| We sent you a new confirmation link. | Te mandamos un enlace de confirmación nuevo. | Message on the confirm email page (verification sent) |
| Sign out to accept it | Salir para aceptarla | Invitation page (from the email link): button |
| Join {project} | Sumarme a {project} | Invitation page (from the email link): button; Your projects page: read by screen readers; Find a project page: read by screen readers |
| to help manage {project} | a ayudar a gestionar {project} | Invitation page (from the email link): fills "You are invited …" |
| to help manage {project}, as a member of the {family} family | a ayudar a gestionar {project}, como integrante de la familia {family} | Invitation page (from the email link): fills "You are invited …" |
| to join the {family} family in {project} | a sumarte a la familia {family} en {project} | Invitation page (from the email link): fills "You are invited …" |
| to join {project} | a sumarte a {project} | Invitation page (from the email link): fills "You are invited …" |
| Choose a password for your account ({email}). | Elegí una contraseña para tu cuenta ({email}). | Invitation page (from the email link): text |
| This invitation is for {email}, but you are signed in as {current}. | Esta invitación es para {email}, pero entraste como {current}. | Invitation page (from the email link): text |
| Invitation to {project} | Invitación a {project} | Invitation page (from the email link): title |
| You are invited | Te invitan | Invitation page (from the email link): title |
| You are invited {what}. | Te invitan {what}. | Invitation page (from the email link): {what} is one of the "to join…" lines |
| {inviter} invites you {what}. | {inviter} te invita {what}. | Invitation page (from the email link): {what} is one of the "to join…" lines |
| Go to sign in | Ir a entrar | Invitation no longer valid page: button |
| It may have expired, been withdrawn, or been used already. Ask whoever invited you to send a new one. | Puede que haya vencido, que la hayan retirado o que ya se haya usado. Pedile a quien te invitó que te mande otra. | Invitation no longer valid page: text |
| Invitation not valid | Invitación no válida | Invitation no longer valid page: title |
| This invitation is no longer valid | Esta invitación ya no es válida | Invitation no longer valid page: title |
| Go to the start | Ir al inicio | Error pages (not found, not allowed, maintenance): button |
| Back soon | Volvemos enseguida | Error pages (not found, not allowed, maintenance): title |
| Not allowed | No permitido | Error pages (not found, not allowed, maintenance): title |
| Page not found | Página no encontrada | Error pages (not found, not allowed, maintenance): title |
| Please try again. If it keeps happening, let us know. | Probá de nuevo. Si sigue pasando, avisanos. | Error pages (not found, not allowed, maintenance): title |
| Something went wrong | Algo salió mal | Error pages (not found, not allowed, maintenance): title |
| This page does not exist, or you do not have access to it. | Esta página no existe, o no tenés acceso. | Error pages (not found, not allowed, maintenance): title |
| Too many attempts | Demasiados intentos | Error pages (not found, not allowed, maintenance): title |
| Wait a moment and try again. | Esperá un momento y probá de nuevo. | Error pages (not found, not allowed, maintenance): title |
| We are doing some maintenance. Please try again in a few minutes. | Estamos haciendo mantenimiento. Probá de nuevo en unos minutos. | Error pages (not found, not allowed, maintenance): title |
| You do not have permission to see this page. | No tenés permiso para ver esta página. | Error pages (not found, not allowed, maintenance): title |
| {count} people | {count} personas | Many pages: Your projects page, Find a project page, Project home page and others: text |
| {count} person | {count} persona | Many pages: Your projects page, Find a project page, Project home page and others: text |
| Admin | Administrador | Many pages: Your projects page, People page, Person card and others |
| When someone invites you, the invitation appears here. You can also find a project to join, or start your own. | Cuando alguien te invita, la invitación aparece acá. También podés encontrar un proyecto para sumarte, o crear el tuyo. | Your projects page: button |
| Decline the invitation to {project}? | ¿Rechazar la invitación a {project}? | Your projects page: confirmation title |
| Decline | Rechazar | Your projects page: dialog text; Family card; Invitations page, admins: title |
| Search projects | Buscar proyectos | Your projects page: example inside a field; Find a project page |
| Join or decline. Joining takes you into the project, with your family and role set. | Sumate o rechazala. Al sumarte entrás al proyecto, con tu familia y tu rol ya puestos. | Your projects page: guided tour, step text |
| Open one to go into it. You can take part in several. | Abrí uno para entrar. Podés participar en varios. | Your projects page: guided tour, step text |
| Start a project for your cooperative: you become its first admin. | Creá un proyecto para tu cooperativa: pasás a ser su primer administrador. | Your projects page: guided tour, step text |
| Invitations waiting for you | Invitaciones que te esperan | Your projects page: guided tour, step title |
| Start your own | Creá el tuyo | Your projects page: guided tour, step title |
| Your projects | Tus proyectos | Your projects page: guided tour, step title; Start a project page: label; The account menu: label; The app's frame: menu and header: text |
| Current | Actual | Your projects page: mark |
| Limited | Limitado | Your projects page: mark; Setup page, admins: text |
| Decline the invitation to {project} | Rechazar la invitación a {project} | Your projects page: read by screen readers |
| From {name} | De {name} | Your projects page: text |
| In the {family} family | En la familia {family} | Your projects page: text |
| Until {date} | Hasta el {date} | Your projects page: text; Family invitations page; Invitations page, admins |
| Find a project | Encontrar un proyecto | Your projects page: text; Find a project page: guided tour, step title; The account menu: label; The app's frame: menu and header |
| As an admin | Como administrador | Your projects page: text; Invitations page, admins: label |
| Start a project | Crear un proyecto | Your projects page: text; Start a project page: title |
| Every project you take part in, and the invitations waiting for you. | Cada proyecto en el que participás, y las invitaciones que te esperan. | Your projects page: title |
| No project matches | Ningún proyecto coincide | Your projects page: title |
| Someone in the project would have to invite you again. | Alguien del proyecto tendría que invitarte de nuevo. | Your projects page: title |
| You are not in any project yet | Todavía no participás en ningún proyecto | Your projects page: title |
| Search by its name, its organization or what it is about. | Buscá por su nombre, su organización o de qué se trata. | Find a project page: guided tour, step text |
| Some projects let you in at once; in others the admins decide, and you hear back from them. Once in, you join your family, or create it if it is not here yet. | Algunos proyectos te dejan entrar en el momento. En otros deciden los administradores, y te avisamos. Una vez adentro, te sumás a tu familia, o la creás si todavía no está. | Find a project page: guided tour, step text |
| Join or ask | Sumarte o pedirlo | Find a project page: guided tour, step title |
| By request | Con aprobación | Find a project page: mark |
| Open to join | Abierto | Find a project page: mark |
| Ask to join {project} | Pedir para sumarme a {project} | Find a project page: read by screen readers |
| Withdraw the request to {project} | Retirar el pedido a {project} | Find a project page: read by screen readers |
| Open {name} | Abrir {name} | Find a project page: read by screen readers; Gallery and files pages: text |
| Not taking anyone new right now | Por ahora no recibe a nadie nuevo | Find a project page: text |
| Open | Abrir | Find a project page: text |
| Projects whose admins list them here. Join at once, or ask them, as each one chooses. | Proyectos que sus administradores muestran acá. Te sumás en el momento o lo pedís, según elija cada uno. | Find a project page: text |
| Request sent | Pedido enviado | Find a project page: text |
| The admins did not accept your request | Los administradores no aceptaron tu pedido | Find a project page: text |
| Withdraw | Retirar | Find a project page: text; Family invitations page: title; Invitations page, admins: title |
| Ask to join | Pedir para sumarme | Find a project page: text; Join your family page: button |
| No project here yet. You can start your own. | Todavía no hay proyectos acá. Podés crear el tuyo. | Find a project page: title |
| No projects found | No encontramos proyectos | Find a project page: title |
| Try other words. | Probá con otras palabras. | Find a project page: title |
| {count} unit | {count} vivienda | Many pages: Find a project page, Setup page, admins, Units page and others: text |
| {count} units | {count} viviendas | Many pages: Find a project page, Setup page, admins, Units page and others: text |
| :name is now in the project. | :name ya está en el proyecto. | Message about a request to join (join accepted) |
| You declined the request of :name. | Rechazaste el pedido de :name. | Message about a request to join (join declined) |
| Your request reached the admins of :project. | Tu pedido llegó a los administradores de :project. | Message about a request to join (join requested) |
| You withdrew your request. | Retiraste tu pedido. | Message about a request to join (join withdrawn) |
| Name | Nombre | Many pages: Start a project page, Add a family form, admins, Unit type form, admins and others |
| Description | Descripción | Many pages: Start a project page, Add a unit form, admins, Unit type form, admins and others |
| What the project is about, how many dwellings… | De qué se trata el proyecto, cuántas viviendas… | Start a project page: example in the description field |
| Every project starts limited, with room for most cooperatives. The {app} team can make it unlimited when you ask. | Todo proyecto empieza limitado, con lugar para la mayoría de las cooperativas. El equipo de {app} puede hacerlo ilimitado cuando lo pidas. | Start a project page: guided tour, step text |
| Its name as its people call it, and what it is about. All of it can be changed later, in its settings. | Su nombre, como lo llama su gente, y de qué se trata. Todo se puede cambiar después, en su configuración. | Start a project page: guided tour, step text |
| About the project | Sobre el proyecto | Start a project page: guided tour, step title |
| Room for the project | Capacidad del proyecto | Start a project page: heading; Setup page, admins: guided tour, step title |
| Federation or institution, if any | Federación o institución, si la hay | Start a project page: hint |
| As its people call it | Como lo llama su gente | Start a project page: hint beside the project name |
| Its first admin | Su primer administrador | Start a project page: label |
| Me: I take part in it as an admin | Yo: participo como administrador | Start a project page: label |
| Organization | Organización | Start a project page: label |
| Someone I invite | Alguien a quien invito | Start a project page: label |
| Their email | Su correo | Start a project page: label; Invite a family member form; Family invitations page |
| Choose its first admin; the rest of its people can be invited afterwards. | Elegí su primer administrador. Al resto se lo puede invitar después. | Start a project page: text |
| Every project starts limited, with room for most cooperatives. The {app} team can make yours unlimited: ask from the project whenever you like. | Todo proyecto empieza limitado, con lugar para la mayoría de las cooperativas. El equipo de {app} puede hacer ilimitado el tuyo: pedilo desde el proyecto cuando quieras. | Start a project page: text |
| Files up to {size}, {total} in all | Archivos de hasta {size}, {total} en total | Start a project page: text |
| Up to {count} families and {units} units | Hasta {count} familias y {units} viviendas | Start a project page: text |
| Up to {count} invitations a day | Hasta {count} invitaciones por día | Start a project page: text |
| Up to {count} people | Hasta {count} personas | Start a project page: text |
| You become its first admin, then set it up step by step: unit types, units, families, invitations. | Pasás a ser su primer administrador y lo ponés a punto paso a paso: tipos de vivienda, viviendas, familias, invitaciones. | Start a project page: text |
| You will be its first admin, and can invite the rest of its people afterwards. | Vas a ser su primer administrador, y después podés invitar al resto de la gente. | Start a project page: text |
| Starting a project | Crear un proyecto | Start a project page: title |
| Lottery | Sorteo | Many pages: Project home page, Lottery page, The lottery's record page and others |
| Families | Familias | Many pages: Project home page, Setup page, admins, People page and others |
| People | Cooperativistas | Many pages: Project home page, Setup page, admins, People page and others |
| Units | Viviendas | Many pages: Project home page, Setup page, admins, Units page and others |
| Share something | Compartir algo | Project home page: button |
| Create an event | Crear un evento | Project home page: button; Events page: text; Quick actions menu, admins: label |
| Its families, people and units: each leads to its page. | Sus familias, cooperativistas y viviendas: cada una lleva a su página. | Project home page: guided tour, step text |
| When it happens, and once it has, which unit your family received. | Cuándo se hace y, una vez hecho, qué vivienda recibió tu familia. | Project home page: guided tour, step text |
| The lottery | El sorteo | Project home page: guided tour, step title |
| The project in figures | El proyecto en cifras | Project home page: guided tour, step title |
| Admins | Administradores | Project home page: heading |
| Joined lately | Se sumaron hace poco | Project home page: heading |
| All events | Todos los eventos | Project home page: link |
| All files | Todos los archivos | Project home page: link |
| All families | Todas las familias | Project home page: link; People page: label |
| Assemblies, work days and meetings show up here. | Acá aparecen las asambleas, las jornadas de trabajo y las reuniones. | Project home page: text |
| Its figures, what was shared lately, the lottery and what is coming up, its people and its units. | Sus números, lo último que se compartió, el sorteo y lo que viene, su gente y sus viviendas. | Project home page: text |
| Make sure your family's ranking is how you want it. | Revisá que el orden de tu familia sea el que quieren. | Project home page: text |
| No events coming up | No hay eventos próximos | Project home page: text |
| Photos of the works, plans and documents, for everyone in the project. | Fotos de la obra, planos y documentos, para todos en el proyecto. | Project home page: text |
| Scheduled for {date}. | Previsto para el {date}. | Project home page: text |
| The admins are adding the units and the families. Everything shows up here as they do. | Los administradores están agregando las viviendas y las familias. Todo va a aparecer acá a medida que lo hagan. | Project home page: text |
| The lottery is done. See the results. | El sorteo terminó. Mirá los resultados. | Project home page: text |
| The lottery is running right now. | El sorteo se está haciendo ahora mismo. | Project home page: text |
| Your family received {unit}. | Tu familia recibió {unit}. | Project home page: text; Lottery page |
| Nothing shared yet | Todavía no se compartió nada | Project home page: text; Platform console: UI Lab samples: title |
| Recently shared | Compartido recientemente | Project home page: title |
| The people of the project | La gente del proyecto | Project home page: title |
| The project at a glance | El proyecto de un vistazo | Project home page: title |
| Your project is getting ready | Tu proyecto se está preparando | Project home page: title |
| Coming up | Próximamente | Project home page: title; Platform console: UI Lab samples |
| :project is approved: its limits are lifted. | :project está aprobado: ya no tiene límites. | Message after acting on a project (project approved) |
| Your project is ready. Start by adding its unit types. | Tu proyecto está listo. Empezá por agregar sus tipos de vivienda. | Message after acting on a project (project created) |
| The project was deleted. | Se borró el proyecto. | Message after acting on a project (project deleted) |
| You left :project. | Saliste de :project. | Message after acting on a project (project left) |
| :project was deleted for good. | :project se borró para siempre. | Message after acting on a project (project purged) |
| :project is back, as it was. | :project volvió, tal como estaba. | Message after acting on a project (project restored) |
| The project was saved. | El proyecto quedó guardado. | Message after acting on a project (project updated) |
| We asked the :app team to make your project unlimited. You will hear from us soon. | Le pedimos al equipo de :app que haga ilimitado tu proyecto. Pronto vas a tener novedades. | Message after asking for unlimited (approval requested) |
| Add the unit types | Agregar los tipos de vivienda | Setup checklist, admins: label |
| Add the units | Agregar las viviendas | Setup checklist, admins: label |
| Schedule the lottery | Fijar la fecha del sorteo | Setup checklist, admins: label |
| Getting to the lottery | Camino al sorteo | Setup checklist, admins: text |
| Ask for unlimited | Pedir ilimitado | Setup page, admins: button |
| Make the project unlimited? | ¿Pedir que el proyecto sea ilimitado? | Setup page, admins: confirmation title |
| A limited project has room for most cooperatives. Should it need more, ask: the {app} team can make it unlimited. | Un proyecto limitado tiene lugar para la mayoría de las cooperativas. Si necesita más, pedilo: el equipo de {app} puede hacerlo ilimitado. | Setup page, admins: guided tour, step text |
| Follow these steps in order. Each one opens the page where you do it, and ticks itself once done. | Seguí estos pasos en orden. Cada uno abre la página donde se hace, y se marca solo cuando está listo. | Setup page, admins: guided tour, step text |
| Its families, its people and its units, with how far each has come. Each card leads where you add them. | Sus familias, su gente y sus viviendas, con cuánto avanzó cada una. Cada tarjeta lleva a donde se agregan. | Setup page, admins: guided tour, step text |
| From empty to the lottery | De cero al sorteo | Setup page, admins: guided tour, step title |
| What a project is made of | De qué está hecho un proyecto | Setup page, admins: guided tour, step title |
| File storage (MB) | Archivos (MB) | Setup page, admins: label |
| Its families, people and units, a click away. And the checklist, from an empty project to its lottery. | Sus familias, su gente y sus viviendas, a un clic. Y la lista de pasos, desde un proyecto vacío hasta su sorteo. | Setup page, admins: text |
| Unlimited was asked for {when}. | Se pidió ilimitado {when}. | Setup page, admins: text |
| What the project has room for: | Para cuánto tiene lugar el proyecto: | Setup page, admins: text |
| {count} type | {count} tipo | Setup page, admins: text |
| {count} types | {count} tipos | Setup page, admins: text |
| {people} people, {invitations} invited | {people} personas, {invitations} invitadas | Setup page, admins: text |
| Add a family | Agregar una familia | Setup page, admins: text; Families page; Quick actions menu, admins: label |
| Invite a member | Invitar a un cooperativista | Setup page, admins: text; Quick actions menu, admins: label |
| Set up units | Configurar viviendas | Setup page, admins: text; Quick actions menu, admins: label |
| Setting up the project | Poner a punto el proyecto | Setup page, admins: title |
| The {app} team looks at the project and lifts its limits. | El equipo de {app} mira el proyecto y le quita los límites. | Setup page, admins: title |
| {units} in {types} | {units} en {types} | Setup page, admins: title |
| {count} families | {count} familias | Setup page, admins: title; Units page: text; Lottery page: admin tools: text; Platform console: Projects: text |
| {count} family | {count} familia | Setup page, admins: title; Units page: text; Lottery page: admin tools: text; Platform console: Projects: text |
| Admins set up the project and run the lottery. | Los administradores ponen a punto el proyecto y hacen el sorteo. | Change role or family form, admins: hint |
| Admin of the project | Administrador del proyecto | Change role or family form, admins: label; Platform console: UI Lab samples |
| Their role and family in this project. | Su rol y su familia en este proyecto. | Change role or family form, admins: text |
| As a guest | Como invitado | Change role or family form, admins: text; Invitations page, admins |
| Not joined their family yet | Todavía no se sumó a su familia | Change role or family form, admins: text; People page; Invitations page, admins |
| Save | Guardar | Many pages: Change role or family form, admins, Add a family form, admins, Add a unit form, admins and others |
| Cancel | Cancelar | Many pages: Change role or family form, admins, Add a family form, admins, Invite a family member form and others: button |
| Family | Familia | Many pages: Change role or family form, admins, People page, Invite a family member form and others |
| Remove | Quitar | Many pages: Person card, Family card, Unit card and others |
| The person was removed from the project. | Se quitó a la persona del proyecto. | Message on the people page (member removed) |
| Saved. | Guardado. | Message on the people page (member updated) |
| Search by name | Buscar por nombre | People page: example inside a field |
| Search by name or email | Buscar por nombre o correo | People page: example inside a field; Platform console: Accounts |
| Everyone | Todos | People page: label |
| Only admins | Solo administradores | People page: label |
| Guest | Invitado | People page: mark; Person card |
| How to see them | Cómo verlos | People page: read by screen readers |
| Invite people | Invitar gente | People page: text |
| Nobody matches. | Nadie coincide. | People page: text |
| Every person in the project, the admins too. Open someone to see what they share. | Todas las personas del proyecto, también los administradores. Elegí a alguien para ver lo que comparte. | People page: title |
| Everyone in the project | Todos en el proyecto | People page: title |
| Edit your profile | Editar tu perfil | Person card: button |
| Remove {name} from the project? | ¿Quitar a {name} del proyecto? | Person card: confirmation title |
| Leave {project}? | ¿Salir de {project}? | Person card: confirmation title; Join your family page |
| Leave | Salir | Person card: dialog text; Join your family page: button; The plan, on the units page: title; Messages (chat) |
| ID number | Cédula | Person card: read by screen readers; Downloaded spreadsheets, admins: text; Your profile page: label |
| Change role or family | Cambiar rol o familia | Person card: text |
| Remove from the project | Quitar del proyecto | Person card: text |
| {family} family | Familia {family} | Person card: text |
| Joined | Se sumó | Person card: text; Downloaded spreadsheets, admins |
| Leave the project | Salir del proyecto | Person card: text; Join your family page: button |
| See the photo of {name} | Ver la foto de {name} | Person card: text; Your profile page |
| Their account stays; they just stop taking part in this project. | Su cuenta sigue. Solo deja de participar en este proyecto. | Person card: title |
| You will stop seeing the project. Someone has to invite you again to come back. | Vas a dejar de ver el proyecto. Para volver, alguien tiene que invitarte de nuevo. | Person card: title |
| It receives a unit of this type | Recibe una vivienda de este tipo | Add a family form, admins: hint |
| They receive the invitation | Le llega la invitación | Add a family form, admins: hint |
| Usually its surnames | Suelen ser los apellidos | Add a family form, admins: hint beside the family name |
| Choose a type | Elegí un tipo | Add a family form, admins: label |
| Email of someone in it | Correo de alguien de la familia | Add a family form, admins: label |
| Add and invite | Agregar e invitar | Add a family form, admins: link |
| Add one first | Agregá uno primero | Add a family form, admins: link |
| Create our family | Crear nuestra familia | Add a family form, admins: link |
| There are no unit types yet. | Todavía no hay tipos de vivienda. | Add a family form, admins: text |
| Add each family with its unit type, and invite someone in it. | Agregá cada familia con su tipo de vivienda e invitá a alguien de ella. | Families page: button |
| Add the first family | Agregar la primera familia | Families page: button |
| Families show up here as people join them. | Las familias aparecen acá a medida que la gente se suma. | Families page: button |
| What is a family? | ¿Qué es una familia? | Families page: confirmation title |
| Search families or people | Buscar familias o personas | Families page: example inside a field; Join your family page |
| See the cooperative family by family, or every person at once. | Mirá la cooperativa familia por familia, o a todas las personas juntas. | Families page: guided tour, step text |
| By family, or everyone | Por familia, o todos | Families page: guided tour, step title |
| All types | Todos los tipos | Families page: label |
| your family | tu familia | Families page: name used for your own family |
| Invite someone into it | Invitar a alguien | Families page: text |
| No family of this type yet. | Todavía no hay familias de este tipo. | Families page: text |
| The family takes part in the lottery as one: its members share one ranking, and it receives one unit. | La familia participa del sorteo como una sola: comparte un orden de preferencias y recibe una vivienda. | Families page: text |
| No family or person matches “{q}”. | Ninguna familia ni persona coincide con «{q}». | Families page: text; Join your family page |
| Bring back the {name} family | Recuperar la familia {name} | Families page: title |
| No families yet | Todavía no hay familias | Families page: title; Invitations page, admins: label |
| Lift the decline | Levantar el rechazo | Family card: button |
| Accept | Aceptar | Family card: button; Invitations page, admins |
| Remove the {name} family? | ¿Quitar la familia {name}? | Family card: confirmation title |
| {name} is not in your family? | ¿{name} no es de tu familia? | Family card: confirmation title |
| It already received a unit | Ya recibió una vivienda | Family card: label |
| {name}: declined | {name}: rechazado | Family card: mark on a family's request |
| Nobody has joined this family yet. | Nadie se sumó a esta familia todavía. | Family card: text |
| Received unit {unit} in the lottery. | Recibió la vivienda {unit} en el sorteo. | Family card: text |
| Unit type: {type} | Tipo de vivienda: {type} | Family card: text |
| Wrong family? | ¿Familia equivocada? | Family card: text |
| {name} asks to join your family | {name} pide sumarse a tu familia | Family card: text |
| Invite a family member | Invitar a un familiar | Family card: text; Quick actions menu, admins: label; The project's menu: label |
| Its members leave the project (admins stay admins). It stays archived. | Sus integrantes salen del proyecto (los administradores siguen siéndolo). Queda archivada. | Family card: title |
| They cannot ask again, unless someone in your family lifts this. | No va a poder volver a pedirlo, salvo que alguien de tu familia levante el rechazo. | Family card: title |
| Choose someone, or write an email below | Elegí a alguien, o escribí un correo abajo | Invite a family member form: label |
| Someone already in the project | Alguien que ya está en el proyecto | Invite a family member form: label |
| Send the invitation | Enviar la invitación | Invite a family member form: text; Family invitations page; Invitations page, admins |
| Unit type | Tipo de vivienda | Many pages: Add a family form, admins, Families page, Add a unit form, admins and others |
| Edit | Editar | Many pages: Family card, Unit card, Event card and others: text |
| :name is in your family now. | :name ya está en tu familia. | Message on the families page (family ask accepted) |
| Your request was cancelled. | Se canceló tu pedido. | Message on the families page (family ask cancelled) |
| You declined :name. | Rechazaste a :name. | Message on the families page (family ask declined) |
| :name can ask again. | :name puede volver a pedirlo. | Message on the families page (family ask undeclined) |
| You asked to join the :family family. | Pediste sumarte a la familia :family. | Message on the families page (family asked) |
| The family was added. | Se agregó la familia. | Message on the families page (family created) |
| The family was removed. | Se quitó la familia. | Message on the families page (family deleted) |
| You left your family. | Saliste de tu familia. | Message on the families page (family left) |
| The family was saved. | La familia quedó guardada. | Message on the families page (family updated) |
| Leave my family | Salir de mi familia | Join your family page: button |
| Mine is not here: create it | La mía no está: crearla | Join your family page: button |
| In the wrong family? | ¿En la familia equivocada? | Join your family page: confirmation title |
| Leave the {family} family? | ¿Salir de la familia {family}? | Join your family page: confirmation title |
| If the {other} family accepts you, {family} is left with nobody and is removed. Ask anyway? | Si la familia {other} te acepta, {family} queda sin nadie y se quita. ¿Pedir igual? | Join your family page: dialog text |
| You are the only one in it, so it is removed. You stay in the project. | Sos la única persona, así que la familia se quita. Seguís en el proyecto. | Join your family page: dialog text |
| Declined your request | Rechazó tu pedido | Join your family page: mark |
| Waiting for an answer | Esperando respuesta | Join your family page: mark; Family invitations page: heading; Invitations page, admins: guided tour, step title |
| Everyone in {project} takes part as a family. If yours is already here, ask to join it. If not, create it. | En {project} todos participan en familia. Si la tuya ya está, pedí sumarte. Si no, creala. | Join your family page: text |
| No families yet. | Todavía no hay familias. | Join your family page: text |
| The lottery has run: families no longer change. | El sorteo ya se hizo: las familias ya no cambian. | Join your family page: text |
| Waiting for the {family} family to answer. | Esperando la respuesta de la familia {family}. | Join your family page: text |
| You are in the {family} family. Ask to join yours, or create it. You stay where you are until it is settled. | Estás en la familia {family}. Pedí sumarte a la tuya, o creala. Seguís donde estás hasta que se resuelva. | Join your family page: text |
| You stay in the project. | Seguís en el proyecto. | Join your family page: text |
| Create your family | Creá tu familia | Join your family page: title |
| Find your family | Buscá tu familia | Join your family page: title |
| You are the only one in {family} | Sos la única persona en {family} | Join your family page: title |
| Withdraw the invitation to {email}? | ¿Retirar la invitación a {email}? | Family invitations page: confirmation title; Invitations page, admins |
| Their email, and their name for the greeting. They join your family directly. | Su correo, y su nombre para el saludo. Entra directamente a tu familia. | Family invitations page: guided tour, step text |
| For the email greeting | Para el saludo del correo | Family invitations page: hint |
| Invite the people of the {family} family to the project. They receive an email with a link. | Invitá a la gente de la familia {family} al proyecto. Reciben un correo con un enlace. | Family invitations page: hint |
| Withdraw the invitation to {email} | Retirar la invitación a {email} | Family invitations page: read by screen readers; Invitations page, admins |
| Invite the rest of your family by email: they join with your family already chosen. | Invitá por correo al resto de tu familia: se suman con tu familia ya elegida. | Family invitations page: text |
| Invite to your family | Invitar a tu familia | Family invitations page: title |
| Inviting your family | Invitar a tu familia | Family invitations page: title |
| by {name} | por {name} | Invitations page, admins: added after a date (also Lottery page: admin tools) |
| Decline the request of {name}? | ¿Rechazar el pedido de {name}? | Invitations page, admins: confirmation title |
| Admins help set up the project and run the lottery. An admin can also be in a family. | Los administradores ayudan a poner a punto el proyecto y hacen el sorteo. También pueden estar en una familia. | Invitations page, admins: guided tour, step text |
| Invitations not accepted yet. You can withdraw them; the link then stops working. | Invitaciones todavía sin aceptar. Si retirás una, su enlace deja de funcionar. | Invitations page, admins: guided tour, step text |
| Members always belong to a family. Their family can then invite the rest of its people by itself. | Los integrantes siempre están en una familia. Después, la familia puede invitar por su cuenta al resto de su gente. | Invitations page, admins: guided tour, step text |
| The person receives an email with a link, valid for two weeks. Without an account, they choose a password; with one, they join after signing in. | La persona recibe un correo con un enlace, válido por dos semanas. Si no tiene cuenta, elige una contraseña. Si la tiene, se suma después de entrar. | Invitations page, admins: guided tour, step text |
| Fellow admins | Otros administradores | Invitations page, admins: guided tour, step title |
| Into which family | En qué familia | Invitations page, admins: guided tour, step title |
| Who to invite | A quién invitar | Invitations page, admins: guided tour, step title |
| People join the project from the link they receive by email. | La gente se suma al proyecto con el enlace que recibe por correo. | Invitations page, admins: hint |
| To help set up the project and run the lottery. | Para ayudar a poner a punto el proyecto y hacer el sorteo. | Invitations page, admins: hint |
| Invite people with an emailed link, valid for two weeks. Members can invite into their own family. Someone already in the project joins their family at once. If others can find the project, those asking to join appear here too. | Invitá con un enlace por correo, válido por dos semanas. Los integrantes pueden invitar a su propia familia. Quien ya está en el proyecto se suma a su familia en el momento. Si otros pueden encontrar el proyecto, quienes piden sumarse también aparecen acá. | Invitations page, admins: text |
| No pending invitations. | No hay invitaciones pendientes. | Invitations page, admins: text |
| This project has room for {members} people, and {day} invitations a day. | Este proyecto tiene lugar para {members} personas, y {day} invitaciones por día. | Invitations page, admins: text |
| Asking to join ({count}) | Piden sumarse ({count}) | Invitations page, admins: title |
| How invitations work | Cómo funcionan las invitaciones | Invitations page, admins: title |
| Invite someone | Invitar a alguien | Invitations page, admins: title |
| Waiting for an answer ({count}) | Esperando respuesta ({count}) | Invitations page, admins: title |
| Invitations | Invitaciones | Invitations page, admins: title; Tabs of settings: label |
| Welcome to :project. | Te damos la bienvenida a :project. | Message after acting on an invitation (invitation accepted) |
| You declined the invitation. | Rechazaste la invitación. | Message after acting on an invitation (invitation declined) |
| The invitation was withdrawn. | Se retiró la invitación. | Message after acting on an invitation (invitation revoked) |
| Invitation sent to :email. | Mandamos la invitación a :email. | Message after acting on an invitation (invitation sent) |
| This invitation is for :email. Sign in with that account to accept it. | Esta invitación es para :email. Entrá con esa cuenta para aceptarla. | Message after acting on an invitation (invitation sign in) |
| For example, A-101 | Por ejemplo, A-101 | Add a unit form, admins: example in the field |
| A-101 | A-101 | Add a unit form, admins: example in the unit name field |
| 2 bedrooms | 2 dormitorios | Add a unit form, admins: example in the unit type name field (also Unit types, admins) |
| Name or number | Nombre o número | Add a unit form, admins: label |
| New type's name | Nombre del tipo nuevo | Add a unit form, admins: label |
| The unit was not added. Try again. | No se pudo agregar la vivienda. Probá de nuevo. | Add a unit form, admins: message |
| Add the unit | Agregar la vivienda | Add a unit form, admins: text |
| Or create a new type | O creá un tipo nuevo | Add a unit form, admins: text |
| Use an existing type | Usar un tipo existente | Add a unit form, admins: text |
| Add and continue with another | Agregar y seguir con otra | Add a unit form, admins: text; Units page, admins: guided tour, step text |
| The unit was added. | Se agregó la vivienda. | Message on the units page, admins (unit created) |
| The unit was removed. | Se quitó la vivienda. | Message on the units page, admins (unit deleted) |
| The unit type was added. | Se agregó el tipo de vivienda. | Message on the units page, admins (unit type created) |
| The unit type was removed. | Se quitó el tipo de vivienda. | Message on the units page, admins (unit type deleted) |
| The unit type was saved. | El tipo de vivienda quedó guardado. | Message on the units page, admins (unit type updated) |
| The unit was saved. | La vivienda quedó guardada. | Message on the units page, admins (unit updated) |
| Remove unit {name}? | ¿Quitar la vivienda {name}? | Unit card: confirmation title |
| A family received it | Ya la recibió una familia | Unit card: label |
| Assigned to | Asignada a | Unit card: text |
| Not assigned yet: the lottery decides which family receives it. | Todavía sin asignar: el sorteo decide qué familia la recibe. | Unit card: text |
| For example, 2 bedrooms | Por ejemplo, 2 dormitorios | Unit type form, admins: example in the field |
| Height on the plan | Alto en el plano | Unit type form, admins: label |
| Width on the plan | Ancho en el plano | Unit type form, admins: label |
| Add the unit type | Agregar el tipo de vivienda | Unit type form, admins: text |
| New units of this type start this size on the plan. If empty, a plain square. | Las viviendas nuevas de este tipo arrancan con este tamaño en el plano. Si está vacío, un cuadrado simple. | Unit type form, admins: text |
| Keep them balanced | Que coincidan | Unit types, admins: guided tour step title: units and families per type |
| A unit type groups equivalent units, like "2 bedrooms". Each family receives a unit of its own type. | Un tipo reúne viviendas equivalentes, como «2 dormitorios». Cada familia recibe una vivienda de su tipo. | Unit types, admins: guided tour, step text |
| Each type should have as many units as families. Types that don't match are marked here. | Cada tipo debería tener tantas viviendas como familias. Acá se marcan los que no coinciden. | Unit types, admins: guided tour, step text |
| Start with the types | Empezá por los tipos | Unit types, admins: guided tour, step title |
| Add the first unit | Agregar la primera vivienda | Units page: button |
| Add the first unit type | Agregar el primer tipo de vivienda | Units page: button |
| Add the units of each type. They appear on the plan, ready to place. | Agregá las viviendas de cada tipo. Aparecen en el plano, listas para ubicar. | Units page: button |
| Start with the types of unit (2 bedrooms, 3 bedrooms…), then add the units of each. | Empezá por los tipos de vivienda (2 dormitorios, 3 dormitorios…) y después agregá las viviendas de cada uno. | Units page: button |
| The admins add the project's units here, by type, and place them on the plan. | Los administradores agregan acá las viviendas del proyecto, por tipo, y las ubican en el plano. | Units page: button |
| Remove {name}? | ¿Quitar {name}? | Units page: confirmation title |
| What is a unit type? | ¿Qué es un tipo de vivienda? | Units page: confirmation title |
| The project's units, by type. | Las viviendas del proyecto, por tipo. | Units page: hint |
| Edit {name} | Editar {name} | Units page: read by screen readers |
| Add a unit type | Agregar un tipo de vivienda | Units page: text |
| Add the units one by one, each with its type. Each one also appears on the plan, ready to place. | Agregá las viviendas una por una, cada una con su tipo. Cada una aparece también en el plano, lista para ubicar. | Units page: text |
| Add units | Agregar viviendas | Units page: text |
| Equivalent units, like '2 bedrooms'. Each family has one type and receives a unit of it, so each type has its own lottery. | Viviendas equivalentes, como «2 dormitorios». Cada familia tiene un tipo y recibe una vivienda de ese tipo: cada tipo tiene su propio sorteo. | Units page: text |
| No units of this type yet. | Todavía no hay viviendas de este tipo. | Units page: text |
| Units of your family's type are highlighted. | Las viviendas del tipo de tu familia aparecen resaltadas. | Units page: text |
| Adding units | Agregar viviendas | Units page: title |
| No units yet | Todavía no hay viviendas | Units page: title |
| Units by type | Viviendas por tipo | Units page: title |
| In use by units or families | Tiene viviendas o familias | Units page: why a unit type cannot be removed |
| "Add and continue with another" saves this unit and starts the next, with the same type. | «Agregar y seguir con otra» guarda esta vivienda y deja lista la próxima, con el mismo tipo. | Units page, admins: guided tour, step text |
| Pick its type, or create a new one here if it does not exist yet. | Elegí su tipo, o creá uno nuevo acá mismo si todavía no existe. | Units page, admins: guided tour, step text |
| Use what people already call it: a number, or a block and floor. | Usá lo que la gente ya conoce: un número, o un bloque y piso. | Units page, admins: guided tour, step text |
| Its type | Su tipo | Units page, admins: guided tour, step title |
| Many at once | Muchas de una vez | Units page, admins: guided tour, step title |
| Name the unit | Nombrá la vivienda | Units page, admins: guided tour, step title |
| The whole plan | Todo el plano | The plan: zoom controls: label |
| Zoom in | Acercar | The plan: zoom controls: label |
| Zoom out | Alejar | The plan: zoom controls: label |
| Hold Ctrl and scroll to zoom the plan | Mantené Ctrl y usá la rueda para acercar el plano | The plan: zoom controls: text |
| Hold ⌘ and scroll to zoom the plan | Mantené ⌘ y usá la rueda para acercar el plano | The plan: zoom controls: text |
| Use two fingers to move the plan | Usá dos dedos para mover el plano | The plan: zoom controls: text |
| number {position} in your ranking | número {position} en tu orden | The plan, on the units page: added after a unit name |
| Keep drawing | Seguir dibujando | The plan, on the units page: button |
| Keep editing | Seguir editando | The plan, on the units page: button |
| Leave without saving the plan? | ¿Salir sin guardar el plano? | The plan, on the units page: confirmation title |
| Still drawing the plan? | ¿Seguís dibujando el plano? | The plan, on the units page: confirmation title |
| The plan closed after a while with no changes. What you drew is saved. | El plano se cerró porque pasó un rato sin cambios. Lo que dibujaste está guardado. | The plan, on the units page: dialog text |
| {name} is drawing the plan right now. Try again in a little while. | {name} está dibujando el plano en este momento. Probá de nuevo en un rato. | The plan, on the units page: dialog text |
| Project plan | Plano del proyecto | The plan, on the units page: read by screen readers; Lottery page: title |
| Floors | Pisos | The plan, on the units page: read by screen readers; Plan editor, admins |
| Done editing | Terminar de editar | The plan, on the units page: text |
| Edit the plan | Editar el plano | The plan, on the units page: text |
| Full screen | Pantalla completa | The plan, on the units page: text |
| Leave full screen | Salir de pantalla completa | The plan, on the units page: text |
| Show people where each unit is, and what surrounds it: buildings, streets, green areas, on as many floors as needed. | Mostrá dónde está cada vivienda y qué tiene alrededor: edificios, calles, áreas verdes, en todos los pisos que hagan falta. | The plan, on the units page: text |
| The plan closes in {seconds} seconds and saves what you drew, so others can draw too. | El plano se cierra en {seconds} segundos y guarda lo que dibujaste, para que otros también puedan dibujar. | The plan, on the units page: text |
| Drawing the plan | Dibujar el plano | The plan, on the units page: title |
| Plan | Plano | The plan, on the units page: title; Platform console: UI Lab samples: label |
| The plan was saved. | El plano quedó guardado. | Message in the plan editor (plan updated) |
| Try again | Reintentar | Plan editor, admins: button |
| Changes save by themselves. Undo and redo are above the plan, and Ctrl+Z works too, even after you reload the page. | Los cambios se guardan solos. Deshacer y rehacer están arriba del plano, y Ctrl+Z también funciona, aunque recargues la página. | Plan editor, admins: guided tour, step text |
| Drag a shape to move it. Its square corners resize it; its round handles move its corners. Double-click an edge to add a corner. | Arrastrá una forma para moverla. Las esquinas cuadradas cambian el tamaño y los puntos redondos mueven los vértices. Hacé doble clic en un borde para agregar un vértice. | Plan editor, admins: guided tour, step text |
| Spread units over floors. Each floor is drawn on its own. | Repartí las viviendas en pisos. Cada piso se dibuja por separado. | Plan editor, admins: guided tour, step text |
| Units appear here by themselves. Add buildings, streets, green areas and other shapes to complete the picture. | Las viviendas aparecen acá solas. Agregá edificios, calles, áreas verdes y otras formas para completar el plano. | Plan editor, admins: guided tour, step text |
| Draw | Dibujar | Plan editor, admins: guided tour, step title |
| Boundary | Límite del terreno | Plan editor, admins: heading |
| Shape | Forma | Plan editor, admins: heading |
| Unit {name} | Vivienda {name} | Plan editor, admins: heading |
| Colour | Color | Plan editor, admins: label |
| Drag to make the plan area taller or shorter | Arrastrá para hacer más alta o más baja el área del plano | Plan editor, admins: label |
| Floor | Piso | Plan editor, admins: label |
| Other | Otro | Plan editor, admins: label |
| Redo | Rehacer | Plan editor, admins: label |
| Snap to grid | Ajustar a la cuadrícula | Plan editor, admins: label |
| Undo | Deshacer | Plan editor, admins: label |
| Unit | Vivienda | Plan editor, admins: label; Downloaded spreadsheets, admins: text; Platform console: Activity: text |
| Add shapes | Agregar formas | Plan editor, admins: read by screen readers |
| Place {unit} on the plan | Ubicar {unit} en el plano | Plan editor, admins: read by screen readers |
| Plan area height | Altura del área del plano | Plan editor, admins: read by screen readers |
| Plan editor | Editor del plano | Plan editor, admins: read by screen readers |
| To place | Por ubicar | Plan editor, admins: read by screen readers |
| Units to place | Viviendas por ubicar | Plan editor, admins: read by screen readers |
| Add a floor | Agregar un piso | Plan editor, admins: text |
| Basement {n} | Subsuelo {n} | Plan editor, admins: text |
| Bring to front | Traer al frente | Plan editor, admins: text |
| Building | Edificio | Plan editor, admins: text |
| Common space | Espacio común | Plan editor, admins: text |
| Drag to move, or drag an edge to move that side. Ctrl + drag makes a copy. Corners resize, and round handles move a corner. Double-click an edge to add one. The handle above rotates, and so does R. Double-click a shape to name it. Right-click for more. | Arrastrá para mover, o arrastrá un borde para mover ese lado. Ctrl + arrastrar hace una copia. Las esquinas cambian el tamaño y los puntos redondos mueven un vértice. Hacé doble clic en un borde para agregar uno. La manija de arriba gira la forma, y también la tecla R. Hacé doble clic en una forma para ponerle nombre. Clic derecho para más. | Plan editor, admins: text |
| Duplicate | Duplicar | Plan editor, admins: text |
| Edit the boundary | Editar el límite | Plan editor, admins: text |
| Entrance | Entrada | Plan editor, admins: text |
| Floor {n} | Piso {n} | Plan editor, admins: text |
| Green area | Área verde | Plan editor, admins: text |
| Ground floor | Planta baja | Plan editor, admins: text |
| Name it | Ponerle nombre | Plan editor, admins: text |
| Not saved yet | Todavía sin guardar | Plan editor, admins: text |
| One floor down | Un piso abajo | Plan editor, admins: text |
| One floor up | Un piso arriba | Plan editor, admins: text |
| Parking | Estacionamiento | Plan editor, admins: text |
| Paste | Pegar | Plan editor, admins: text |
| Playground | Juegos infantiles | Plan editor, admins: text |
| Remove the corner | Quitar el vértice | Plan editor, admins: text |
| Rotate | Girar | Plan editor, admins: text |
| Rotate (R) | Girar (R) | Plan editor, admins: text |
| Saving… | Guardando… | Plan editor, admins: text |
| Select a shape to change it. | Elegí una forma para cambiarla. | Plan editor, admins: text |
| Send to back | Enviar al fondo | Plan editor, admins: text |
| Street | Calle | Plan editor, admins: text |
| The outline of the land. Move its corners, or double-click an edge to add one. | El contorno del terreno. Mové sus vértices, o hacé doble clic en un borde para agregar uno. | Plan editor, admins: text |
| Saved | Guardado | Plan editor, admins: text; Lottery page; Lottery page: saving a ranking |
| Delete | Borrar | Plan editor, admins: text; Messages (chat): title; Platform console: Accounts: dialog text |
| Add a unit | Agregar una vivienda | Plan editor, admins: title |
| Saved as you go | Se guarda solo | Plan editor, admins: tour step title: ranking |
| Confirm before running the lottery. | Confirmá antes de hacer el sorteo. | Lottery page: admin running it |
| Invalidate | Invalidar | Lottery page: admin tools: button |
| Invalidate the lottery? | ¿Invalidar el sorteo? | Lottery page: admin tools: confirmation title |
| Run the lottery now? | ¿Hacer el sorteo ahora? | Lottery page: admin tools: confirmation title |
| Every assignment is cleared and the lottery can run again. Everyone in the project will read who did it and why. | Se borran todas las asignaciones y el sorteo se puede volver a hacer. Todos en el proyecto van a leer quién lo hizo y por qué. | Lottery page: admin tools: dialog text |
| Every family receives a unit from its ranking. The result is final. Only an invalidation, with a reason everyone reads, can undo it. | Cada familia recibe una vivienda según su orden de preferencias. El resultado es definitivo. Solo se puede deshacer invalidándolo, con un motivo que todos leen. | Lottery page: admin tools: dialog text |
| Run anyway | Hacer el sorteo igual | Lottery page: admin tools: dialog text |
| The earliest it can run | Desde cuándo se puede hacer | Lottery page: admin tools: hint beside the lottery date |
| Why it is invalidated | Por qué se invalida | Lottery page: admin tools: label |
| Not set | Sin fijar | Lottery page: admin tools: no lottery date yet |
| Failed | Falló | Lottery page: admin tools: status of a lottery run or a deployment (also Platform console: Deployments) |
| Add unit types, units and families first. | Primero agregá los tipos de vivienda, las viviendas y las familias. | Lottery page: admin tools: text |
| Completed | Completado | Lottery page: admin tools: text |
| Date | Fecha | Lottery page: admin tools: text |
| Description and date | Descripción y fecha | Lottery page: admin tools: text |
| Invalidated by {name}: | Invalidado por {name}: | Lottery page: admin tools: text |
| It can run once its date arrives. | Se puede hacer cuando llegue su fecha. | Lottery page: admin tools: text |
| Its record | Su registro | Lottery page: admin tools: text |
| Lottery description and date | Descripción y fecha del sorteo | Lottery page: admin tools: text |
| Once its date arrives, you run the lottery and every family receives a unit from its ranking, fairly and in one go. The result is final, and recorded. | Cuando llega la fecha, hacés el sorteo y cada familia recibe una vivienda según su orden, de forma justa y de una sola vez. El resultado es definitivo y queda registrado. | Lottery page: admin tools: text |
| Set its date first. | Primero fijá su fecha. | Lottery page: admin tools: text |
| Some types have more units than families (or fewer): you will be asked to confirm. | Algunos tipos tienen más viviendas que familias, o menos: te vamos a pedir que confirmes. | Lottery page: admin tools: text |
| The lottery is done: every family has its unit. | El sorteo terminó: cada familia tiene su vivienda. | Lottery page: admin tools: text |
| They stay as they were once the lottery ran. | Quedan fijas una vez hecho el sorteo. | Lottery page: admin tools: text |
| Running | En curso | Lottery page: admin tools: text; Platform console: Deployments |
| Invalidated | Invalidado | Lottery page: admin tools: text; The lottery's record page |
| Before running | Antes del sorteo | Lottery page: admin tools: title |
| Readiness | Preparación | Lottery page: admin tools: title |
| Runs | Sorteos | Lottery page: admin tools: title |
| RUN | SORTEAR | Lottery page: admin tools: word the admin types to confirm |
| :unit_type has :units units for :families families | :unit_type tiene :units viviendas para :families familias | Lottery page: admin, numbers differ |
| :unit_type has :units units for :families families | :unit_type tiene :units viviendas para :families familias | Lottery page: admin, numbers differ |
| The number of units and families differ: | La cantidad de viviendas y de familias no coincide: | Lottery page: admin, numbers differ |
| The lottery gives each family a unit, from the rankings the families made. | El sorteo le da a cada familia una vivienda, según el orden de preferencias que eligió. | Lottery page: description until an admin writes one |
| Move {unit} down | Bajar {unit} | Lottery page: family's ranking list: read by screen readers |
| Move {unit} up | Subir {unit} | Lottery page: family's ranking list: read by screen readers |
| Move {unit}, number {position} | Mover {unit}, número {position} | Lottery page: family's ranking list: read out by screen readers |
| Picked up {unit}, number {position} | Tomaste {unit}, número {position} | Lottery page: family's ranking list: read out by screen readers while moving a unit |
| {unit} goes to number {position} | {unit} pasa al número {position} | Lottery page: family's ranking list: read out by screen readers while moving a unit |
| {unit} is now number {position} | {unit} ahora es la número {position} | Lottery page: family's ranking list: read out by screen readers while moving a unit |
| {unit} stays where it was | {unit} queda donde estaba | Lottery page: family's ranking list: read out by screen readers while moving a unit |
| To move a unit, press Space, move it with the arrow keys, and press Space again to drop it. Escape cancels. | Para mover una vivienda, apretá Espacio, movela con las flechas y apretá Espacio de nuevo para soltarla. Escape cancela. | Lottery page: family's ranking list: text |
| Drag a unit, or use its arrows, to move it up or down. Anyone in your family can change the order: your family has one ranking. | Arrastrá una vivienda, o usá sus flechas, para subirla o bajarla. Cualquiera de tu familia puede cambiar el orden: es uno solo para toda la familia. | Lottery page: guided tour, step text |
| Each family ranks the units of its type. The lottery first gives the family that would be worst off the best it can, then makes the whole project as happy as possible. Only ties are settled at random. | Cada familia ordena las viviendas de su tipo. El sorteo primero cuida a la familia que quedaría peor, y le da lo mejor posible. Después busca la mayor conformidad de todo el proyecto. Solo los empates se resuelven al azar. | Lottery page: guided tour, step text |
| Every change is saved as you make it, until the lottery runs. | Cada cambio se guarda al momento, hasta que se haga el sorteo. | Lottery page: guided tour, step text |
| Every family and the unit it received, yours marked. | Cada familia y la vivienda que recibió, con la tuya marcada. | Lottery page: guided tour, step text |
| The units of your type stand out, each with its place in your ranking. Drag one from the plan onto your list to place it there. | Las viviendas de tu tipo se destacan, cada una con su lugar en tu orden. Arrastrá una desde el plano hasta tu lista para ubicarla ahí. | Lottery page: guided tour, step text |
| These are the units of your family's type, most wanted first. The lottery gives every family a unit as high in its ranking as possible. | Estas son las viviendas del tipo de tu familia, de la que más quieren a la que menos. El sorteo le da a cada familia una vivienda lo más arriba posible en su orden. | Lottery page: guided tour, step text |
| When the lottery happens, whether it is running, and once it has, which unit each family received. | Cuándo es el sorteo, si se está haciendo y, una vez hecho, qué vivienda recibió cada familia. | Lottery page: guided tour, step text |
| How it decides | Cómo decide | Lottery page: guided tour, step title |
| No need to save | No hace falta guardar | Lottery page: guided tour, step title |
| On the plan | En el plano | Lottery page: guided tour, step title |
| Reorder it | Cambialo | Lottery page: guided tour, step title |
| The results | Los resultados | Lottery page: guided tour, step title |
| Where it stands | Cómo va | Lottery page: guided tour, step title |
| Running the lottery | Hacer el sorteo | Lottery page: guided tour, step title; Lottery page: admin tools: read by screen readers |
| Your preferences | Tus preferencias | Lottery page: guided tour, step title; Lottery page: the plan: read by screen readers |
| Scheduled for {date} | Previsto para el {date} | Lottery page: heading |
| The lottery is done | El sorteo terminó | Lottery page: heading |
| Something went wrong while running the lottery. Try again. If it keeps failing, contact the platform team. | Algo salió mal al hacer el sorteo. Probá de nuevo. Si sigue fallando, avisale al equipo de la plataforma. | Lottery page: if the lottery fails |
| The lottery cannot find a result with the current rankings. Contact the platform team. | El sorteo no encuentra un resultado con las preferencias actuales. Avisale al equipo de la plataforma. | Lottery page: if the lottery fails |
| The lottery could not finish. Contact the platform team. | El sorteo no pudo terminar. Avisale al equipo de la plataforma. | Lottery page: if the lottery fails |
| The lottery took too long and was stopped. Try again. | El sorteo tardó demasiado y se detuvo. Probá de nuevo. | Lottery page: if the lottery fails |
| No unit | Sin vivienda | Lottery page: mark; The lottery's record page |
| Lottery | Sorteo | Lottery page: page title |
| A lottery run was invalidated · {date} | Se invalidó un sorteo · {date} | Lottery page: text |
| Check the result | Verificar el resultado | Lottery page: text |
| Nobody has joined these families yet, only invited: {families}. | Estas familias todavía no tienen a nadie, solo invitaciones: {families}. | Lottery page: text |
| Not scheduled yet | Todavía sin fecha | Lottery page: text |
| Saving | Guardando | Lottery page: text |
| The lottery can't run right now. We're looking into it. Try again later. | Ahora no se puede hacer el sorteo. Ya lo estamos revisando. Probá más tarde. | Lottery page: text |
| The result is final. If a run is ever invalidated, everyone sees who did it and why. | El resultado es definitivo. Si alguna vez se invalida un sorteo, todos ven quién lo hizo y por qué. | Lottery page: text |
| Until then, every family can change its ranking as often as it likes. | Hasta entonces, cada familia puede cambiar su orden de preferencias todas las veces que quiera. | Lottery page: text |
| {name} invalidated a lottery run · {date} | {name} invalidó un sorteo · {date} | Lottery page: text |
| {type} units are highlighted, with their place in your ranking. | Las viviendas {type} están resaltadas, con su lugar en tu orden. | Lottery page: text |
| {type} units, as your family ranked them. | Viviendas {type}, en el orden que eligió tu familia. | Lottery page: text |
| {type} units, most wanted first. Changes are saved as you make them, for the whole family. | Viviendas {type}, de la que más quieren a la que menos. Los cambios se guardan al momento, para toda la familia. | Lottery page: text |
| Drag a unit from the plan onto a place to rank it there. | Arrastrá una vivienda del plano a un lugar para ordenarla ahí. | Lottery page: the plan: hint |
| How the lottery works | Cómo funciona el sorteo | Lottery page: title |
| Results | Resultados | Lottery page: title |
| The lottery already ran, or is running. This can no longer change. | El sorteo ya se hizo, o se está haciendo. Esto ya no puede cambiar. | Lottery page: when changing something after the lottery |
| The lottery is running. Units and families can change once it ends. | El sorteo está en curso. Las viviendas y las familias pueden cambiar cuando termine. | Lottery page: when changing something during the lottery |
| Checking every family's result | Revisando el resultado de cada familia | Lottery page: while the lottery runs: text |
| Finding what gives every family the most | Buscando lo mejor para cada familia | Lottery page: while the lottery runs: text |
| Reading every family's preferences | Leyendo las preferencias de cada familia | Lottery page: while the lottery runs: text |
| The lottery is running | El sorteo está en curso | Lottery page: while the lottery runs: text |
| Weighing the units of each type | Repasando las viviendas de cada tipo | Lottery page: while the lottery runs: text |
| Writing the results… | Anotando los resultados… | Lottery page: while the lottery runs: text |
| At least two families are needed to run the lottery. | Hacen falta al menos dos familias para hacer el sorteo. | Lottery page: why it cannot run, admins |
| For now this project has room for lotteries of up to :max families. Ask for unlimited to lift it. | Por ahora este proyecto tiene lugar para sorteos de hasta :max familias. Pedí ilimitado para quitar el límite. | Lottery page: why it cannot run, admins |
| Set the lottery's date before running it. | Fijá la fecha del sorteo antes de hacerlo. | Lottery page: why it cannot run, admins |
| The lottery already ran. | El sorteo ya se hizo. | Lottery page: why it cannot run, admins |
| The lottery can't run right now. We're looking into it. Try again later. | Ahora no se puede hacer el sorteo. Ya lo estamos revisando. Probá más tarde. | Lottery page: why it cannot run, admins |
| The lottery cannot run before its date. | El sorteo no se puede hacer antes de su fecha. | Lottery page: why it cannot run, admins |
| Each type should have as many units as families. If not, you are asked to confirm: what is left over of each type goes into one last round among everyone left over. | Cada tipo debería tener tantas viviendas como familias. Si no, te pedimos que confirmes: lo que sobre de cada tipo pasa a una última ronda entre quienes hayan quedado sin vivienda. | Lottery page, admins: guided tour, step text |
| From this date on, the lottery can run. Until then, families can change their rankings. Everyone reads the description at the top of this page. | Desde esta fecha se puede hacer el sorteo. Hasta entonces, las familias pueden cambiar su orden de preferencias. La descripción la leen todos, arriba en esta página. | Lottery page, admins: guided tour, step text |
| Once its date arrives. Everyone sees it work, step by step. Then each family finds its unit on this page, and everyone is told. The result is final. | Cuando llega su fecha. Todos lo ven avanzar, paso a paso. Después cada familia encuentra su vivienda en esta página, y se avisa a todos. El resultado es definitivo. | Lottery page, admins: guided tour, step text |
| Only in exceptional cases: invalidating clears every assignment and asks for a reason, which everyone in the project reads. | Solo en casos excepcionales: invalidar borra todas las asignaciones y pide un motivo, que todos en el proyecto leen. | Lottery page, admins: guided tour, step text |
| Its date | Su fecha | Lottery page, admins: guided tour, step title |
| Ready, type by type | Todo listo, tipo por tipo | Lottery page, admins: guided tour, step title |
| Running it | Hacerlo | Lottery page, admins: guided tour, step title |
| Undoing a run | Deshacer un sorteo | Lottery page, admins: guided tour, step title |
| The lottery was invalidated. Everyone in the project can read why. | Se invalidó el sorteo. Todos en el proyecto pueden leer por qué. | Message on the lottery page (lottery invalidated) |
| The lottery date was set. | Se fijó la fecha del sorteo. | Message on the lottery page (lottery scheduled) |
| The lottery is running. Results appear here in a moment. | El sorteo está en marcha. Los resultados aparecen acá en un momento. | Message on the lottery page (lottery started) |
| What it decided, and from what: written as it ran, never changed. | Qué decidió y a partir de qué: anotado mientras se hacía, y nunca cambiado. | The lottery's record page: hint |
| Average place | Lugar promedio | The lottery's record page: label |
| Worst place | Peor lugar | The lottery's record page: label |
| 1 is a first choice | 1 es una primera opción | The lottery's record page: text |
| Computed from everything the lottery read and decided. Note it down: if the record were ever changed, it would no longer match. | Calculada a partir de todo lo que el sorteo leyó y decidió. Anotala: si alguna vez se cambiara el registro, ya no coincidiría. | The lottery's record page: text |
| Download the full record | Descargar el registro completo | The lottery's record page: text |
| Each family’s place is where its unit stood in the ranking the family had when the lottery ran: 1 is its first choice. The lottery first made the worst place as good as it could, then the places as good as possible for everyone. | El lugar de cada familia es la posición que tenía su vivienda en su orden cuando se hizo el sorteo: 1 es su primera opción. El sorteo primero hizo el peor lugar lo mejor posible. Después, los lugares lo mejor posible para todos. | The lottery's record page: text |
| Final | Definitivo | The lottery's record page: text |
| run by {name} | hecho por {name} | The lottery's record page: text |
| {place} of {count} | {place} de {count} | The lottery's record page: text |
| Checking the result | Verificar el resultado | The lottery's record page: title |
| Each family | Cada familia | The lottery's record page: title |
| Fingerprint | Huella | The lottery's record page: title |
| The lottery’s record | El registro del sorteo | The lottery's record page: title |
| Your family’s ranking, as recorded | El orden de tu familia, tal como quedó registrado | The lottery's record page: title |
| Keep it | Mantenerlo | Event card: button |
| Cancel {title}? | ¿Cancelar {title}? | Event card: confirmation title |
| Cancel the event | Cancelar el evento | Event card: dialog text |
| Will you go? | ¿Vas a ir? | Event card: heading; Events page: guided tour, step title |
| Join online | Entrar en línea | Event card: link |
| Going ({count}) | Van ({count}) | Event card: list of who attends an event |
| Draft | Borrador | Event card: mark |
| Happening now | Ahora | Event card: mark |
| Past | Pasados | Event card: mark; Events page: label; Platform console: UI Lab samples: label |
| I can't | No puedo | Event card: text |
| I'll go | Voy | Event card: text |
| No | No | Event card: text |
| No date yet | Todavía sin fecha | Event card: text |
| Posted by {name} | Publicado por {name} | Event card: text |
| Yes | Sí | Event card: text; Downloaded spreadsheets, admins |
| Online | En línea | Event card: text; Event form, admins: label |
| The event is removed for everyone. | El evento se quita para todos. | Event card: title |
| Drafts are visible to admins only. | Los borradores solo los ven los administradores. | Event form, admins: hint |
| Starting with https:// | Empieza con https:// | Event form, admins: hint |
| One hour if empty | Una hora si queda vacío | Event form, admins: hint beside the end of an event |
| Empty if not decided | Vacío si todavía no se sabe | Event form, admins: hint beside the place of an event |
| Ask people whether they will go | Preguntar si van a ir | Event form, admins: label |
| Ends | Termina | Event form, admins: label |
| Link to join | Enlace para sumarse | Event form, admins: label |
| On site | Presencial | Event form, admins: label |
| Published | Publicado | Event form, admins: label |
| Starts | Empieza | Event form, admins: label |
| Where | Dónde | Event form, admins: label |
| Title | Título | Event form, admins: label; Demo data: sample file description: text; Forum page |
| Place | Lugar | Event form, admins: label; Platform console: UI Lab samples |
| Create the event | Crear el evento | Event form, admins: text |
| Meetings, assemblies and activities of the project. | Reuniones, asambleas y actividades del proyecto. | Events page: guided tour, step text |
| When an event asks, answer with one tap, so organizers know how many to expect. | Cuando un evento lo pregunta, respondé con un toque: así quienes organizan saben cuántos van. | Events page: guided tour, step text |
| Upcoming and past | Próximos y pasados | Events page: guided tour, step title |
| Upcoming | Próximos | Events page: label; Platform console: UI Lab samples |
| Meetings, assemblies and activities, published by the admins of the project. Some ask whether you will go. | Reuniones, asambleas y actividades que publican los administradores del proyecto. Algunas preguntan si vas a ir. | Events page: text |
| No event matches | Ningún evento coincide | Events page: text |
| No past events | No hay eventos pasados | Events page: text |
| No upcoming events | No hay próximos eventos | Events page: text |
| About events | Sobre los eventos | Events page: title |
| Events | Eventos | Events page: title; Project options, in settings and when starting a project: label; The project's menu: label; Platform console: Projects: label |
| Great, see you there. | Bien, nos vemos ahí. | Message after answering an event (rsvp attending) |
| Thanks for letting us know. | Gracias por avisar. | Message after answering an event (rsvp not attending) |
| The event was created. | Se creó el evento. | Message on the events page (event created) |
| The event was cancelled. | Se canceló el evento. | Message on the events page (event deleted) |
| The event was saved. | El evento quedó guardado. | Message on the events page (event updated) |
| Remove this file? | ¿Quitar este archivo? | Gallery and files pages: confirmation title |
| Photos and videos form the gallery; documents and audio have their own lists. | Las fotos y los videos forman la galería; los documentos y el audio tienen sus propias listas. | Gallery and files pages: guided tour, step text |
| Upload one or several files at once, with a short description. Only people in the project can see them. | Subí uno o varios archivos a la vez, con una descripción corta. Solo la gente del proyecto puede verlos. | Gallery and files pages: guided tour, step text |
| Kinds of files | Tipos de archivo | Gallery and files pages: guided tour, step title |
| Share yours | Compartí los tuyos | Gallery and files pages: guided tour, step title |
| Read aloud by screen readers | La leen los lectores de pantalla | Gallery and files pages: hint beside a picture description |
| Text for people who cannot see the image | Texto para quienes no pueden ver la imagen | Gallery and files pages: label |
| Media | Multimedia | Gallery and files pages: label; The project's menu |
| Download {name} | Descargar {name} | Gallery and files pages: read by screen readers |
| Pause | Pausar | Gallery and files pages: read by screen readers |
| Play | Reproducir | Gallery and files pages: read by screen readers |
| Position in the recording | Posición en la grabación | Gallery and files pages: read by screen readers |
| Previous | Anterior | Gallery and files pages: read by screen readers |
| Speed: {speed} | Velocidad: {speed} | Gallery and files pages: read by screen readers |
| Storage used | Espacio usado | Gallery and files pages: read by screen readers |
| Next | Siguiente | Gallery and files pages: read by screen readers; Guided tour controls: button |
| Be the first to share something. | Empezá vos: compartí algo. | Gallery and files pages: text |
| Download | Descargar | Gallery and files pages: text |
| Download the document | Descargar el documento | Gallery and files pages: text |
| Everyone in the project can share and see them; nobody outside it can. You can edit or remove what you shared. | Todos en el proyecto pueden compartirlos y verlos; nadie de afuera. Podés editar o quitar lo que compartiste. | Gallery and files pages: text |
| Removed files stay stored, and still count. | Los archivos quitados siguen guardados, y cuentan. | Gallery and files pages: text |
| Share files | Compartir archivos | Gallery and files pages: text |
| Share photos | Compartir fotos | Gallery and files pages: text |
| Shared by {name} | Compartido por {name} | Gallery and files pages: text |
| Storage used: {used} of {limit}. | Espacio usado: {used} de {limit}. | Gallery and files pages: text |
| The project's photos and videos, and its records: documents and recordings. | Las fotos y videos del proyecto, y sus registros: documentos y grabaciones. | Gallery and files pages: text |
| The project's photos and videos. | Las fotos y los videos del proyecto. | Gallery and files pages: text |
| The project's records: documents and recordings. | Los registros del proyecto: documentos y grabaciones. | Gallery and files pages: text |
| {position} of {count} | {position} de {count} | Gallery and files pages: text |
| About files | Sobre los archivos | Gallery and files pages: title |
| Nothing here yet | Todavía no hay nada | Gallery and files pages: title |
| Nothing matches | Nada coincide | Gallery and files pages: title |
| Gallery | Galería | Gallery and files pages: title; The project's menu: label |
| Files | Archivos | Many pages: Gallery and files pages, Share files form, The project's menu and others |
| The file was removed. | Se quitó el archivo. | Message on gallery and files (media deleted) |
| The description was saved. | La descripción quedó guardada. | Message on gallery and files (media updated) |
| Your file is shared.\|Your :count files are shared. | Compartiste tu archivo.\|Compartiste tus :count archivos. | Message on gallery and files (media uploaded) |
| That was too much to send at once. Share fewer files, or smaller ones. | Era demasiado para mandar de una vez. Compartí menos archivos, o más livianos. | Message when sharing files (upload too large) |
| Building site, week 12 | La obra, semana 12 | Share files form: example file description |
| Up to {count} at once | Hasta {count} a la vez | Share files form: hint |
| Too large | Demasiado grande | Share files form: mark on a file that cannot be sent (also Platform console: UI Lab samples) |
| Uploading | Subiendo | Share files form: read by screen readers |
| Share | Compartir | Share files form: text |
| The project uses {used} of its {limit}. | El proyecto usa {used} de sus {limit}. | Share files form: text |
| Together they are {size}. You can send up to {limit} at once. | Entre todos ocupan {size}. Podés enviar hasta {limit} de una vez. | Share files form: text |
| Up to {size} each | Hasta {size} cada uno | Share files form: text |
| {name} cannot be shared: photos, videos, audio, PDF and office documents only. | No se puede compartir {name}: solo fotos, videos, audio, PDF y documentos de oficina. | Share files form: text |
| Choose files or drop them here | Elegí archivos o soltalos acá | Share files form: text; Platform console: UI Lab samples |
| Open the first one | Abrí el primero | Forum page: button |
| Questions, ideas and news, for everyone in the project. | Preguntas, ideas y novedades, para todos los del proyecto. | Forum page: button |
| Search the topics | Buscar en los temas | Forum page: example inside a field |
| Open a topic for a question, an idea or news: everyone in the project is told. | Abrí un tema para una pregunta, una idea o una novedad: se avisa a todos los del proyecto. | Forum page: guided tour, step text |
| Pinned ones first, then those with the latest posts. Whoever wrote in a topic is told of each reply. | Primero los fijados, después los de mensajes más recientes. A quienes escribieron en un tema les avisamos de cada respuesta. | Forum page: guided tour, step text |
| Start or join a conversation | Empezá una conversación o sumate a una | Forum page: guided tour, step title |
| The topics | Los temas | Forum page: guided tour, step title |
| Open the topic | Abrir el tema | Forum page: label |
| Message | Mensaje | Forum page: label; Forum topic page: read by screen readers; Messages (chat): read by screen readers |
| {count} replies | {count} respuestas | Forum page: mark |
| {count} reply | {count} respuesta | Forum page: mark |
| Closed | Cerrado | Forum page: read by screen readers |
| Pinned | Fijado | Forum page: read by screen readers |
| Conversations among everyone in the project: open a topic, or join one. Admins can pin a topic to the top, or close it. | Conversaciones entre todos los del proyecto: abrí un tema o sumate a uno. Los administradores pueden fijar un tema arriba, o cerrarlo. | Forum page: text |
| Open a topic | Abrir un tema | Forum page: text |
| Someone who left | Alguien que ya no está | Forum page: text; Forum topic page; Messages (chat) |
| No topic matches | Ningún tema coincide | Forum page: title |
| No topics yet | Todavía no hay temas | Forum page: title |
| The forum | El foro | Forum page: title |
| Forum | Foro | Forum page: title; Forum topic page: label; Project options, in settings and when starting a project: label; The project's menu: label |
| Remove this post? | ¿Quitar este mensaje? | Forum topic page: confirmation title |
| Remove this topic? | ¿Quitar este tema? | Forum topic page: confirmation title |
| Reply | Responder | Forum topic page: label |
| Your reply | Tu respuesta | Forum topic page: label |
| Post options | Opciones del mensaje | Forum topic page: read by screen readers |
| Remove the topic | Quitar el tema | Forum topic page: read by screen readers |
| edited | editado | Forum topic page: text |
| Reopen | Reabrir | Forum topic page: text |
| This topic is closed: it takes no more replies. | Este tema está cerrado: no admite más respuestas. | Forum topic page: text |
| Pin | Fijar | Forum topic page: text; Messages (chat): read by screen readers |
| Unpin | Desfijar | Forum topic page: text; Messages (chat): read by screen readers |
| Close | Cerrar | Forum topic page: text; The app's frame: menu and header: read by screen readers; Startup error, when words cannot load |
| Every post in it goes too. | También se van todos sus mensajes. | Forum topic page: title |
| Your topic is open. | Tu tema está abierto. | Message on the forum (topic opened) |
| The topic was removed. | Se quitó el tema. | Message on the forum (topic removed) |
| Open the guide | Abrir la guía | Assistant: button |
| Thank you for asking. I have answered all I can for you today. Write again tomorrow and I will gladly help. Until then, the guide has plenty of answers. | Gracias por preguntar. Por hoy ya te respondí todo lo que podía. Escribime mañana y con gusto te ayudo. Hasta entonces, en la guía vas a encontrar muchas respuestas. | Assistant: daily limit of a person |
| I have had a lot of questions today and cannot answer any more. Try again tomorrow and I will gladly help. Until then, the guide has plenty of answers. | Hoy recibí muchas preguntas y ya no puedo responder más. Probá de nuevo mañana y con gusto te ayudo. Hasta entonces, en la guía vas a encontrar muchas respuestas. | Assistant: daily limit of the app |
| Assistant | Asistente | Assistant: its name |
| add the unit types | agregar los tipos de vivienda | Assistant: part of "Still to do" |
| add the units | agregar las viviendas | Assistant: part of "Still to do" |
| invite the families | invitar a las familias | Assistant: part of "Still to do" |
| run the lottery | hacer el sorteo | Assistant: part of "Still to do" |
| schedule the lottery | fijar la fecha del sorteo | Assistant: part of "Still to do" |
| How do I invite my family? | ¿Cómo invito a mi familia? | Assistant: questions it offers: text; Messages (chat) |
| How do I rank the units? | ¿Cómo ordeno las viviendas? | Assistant: questions it offers: text; Messages (chat) |
| How does the lottery work? | ¿Cómo funciona el sorteo? | Assistant: questions it offers: text; Messages (chat) |
| What can I do here? | ¿Qué puedo hacer acá? | Assistant: questions it offers: text; Messages (chat) |
| What is left to set up? | ¿Qué falta poner a punto? | Assistant: questions it offers: text; Messages (chat) |
| Here you follow your project: its people, its units and the lottery, all in one place. | Acá seguís tu proyecto: su gente, sus viviendas y el sorteo, todo en un mismo lugar. | Assistant: ready answer, can do |
| Show me around | Mostrame cómo es | Assistant: ready answer, can do action |
| As an admin, you also set the project up and invite people. | Como administrador, además ponés a punto el proyecto e invitás a la gente. | Assistant: ready answer, can do manage |
| You can also rank the units your family would like. | También podés ordenar las viviendas que le gustarían a tu familia. | Assistant: ready answer, can do rank |
| Hello. What would you like to know? | ¡Hola! ¿Qué querés saber? | Assistant: ready answer, hello |
| Open your family's card and press Invite a family member. Write their email and send. They receive the invitation. | Abrí la tarjeta de tu familia y tocá Invitar a un familiar. Escribí su correo y mandala. Le llega la invitación. | Assistant: ready answer, invite |
| Invite someone | Invitar a alguien | Assistant: ready answer, invite action |
| Each family ranks the units of its type, from the most to the least wanted. On its date, an admin runs the lottery: it first makes the family that would be worst off as well off as possible, then the whole project as satisfied as possible. Only ties are broken at random. | Cada familia ordena las viviendas de su tipo, de la que más quiere a la que menos. Cuando llega la fecha, un administrador hace el sorteo. El sorteo primero cuida a la familia que quedaría peor, y le da lo mejor posible. Después busca la mayor conformidad de todo el proyecto. Solo los empates se resuelven al azar. | Assistant: ready answer, lottery |
| See how it works | Ver cómo funciona | Assistant: ready answer, lottery action |
| In Lottery you rank the units of your family's type, from the most to the least wanted. Every change is saved at once, for the whole family, until the lottery runs. Only your family sees your ranking. | En Sorteo ordenás las viviendas del tipo de tu familia, de la que más querés a la que menos. Cada cambio se guarda al momento, para toda la familia, hasta que se haga el sorteo. Solo tu familia ve tu orden. | Assistant: ready answer, rank |
| Go to the lottery | Ir al sorteo | Assistant: ready answer, rank action |
| Go to Setup | Ir a Puesta a punto | Assistant: ready answer, setup action |
| Everything is ready: nothing is left to set up. | Está todo listo. No falta nada por poner a punto. | Assistant: ready answer, setup done |
| Still to do: :steps. | Falta: :steps. | Assistant: ready answer, setup left |
| You are welcome. I am here whenever you need me. | De nada. Acá estoy cuando me necesites. | Assistant: ready answer, thanks |
| Sorry, I could not answer just now. Try again in a moment. | Perdón, ahora no pude responder. Probá de nuevo en un momento. | Assistant: when it failed |
| I cannot answer right now. Try again in a little while and I will gladly help. Until then, the guide has plenty of answers. | Ahora no puedo responder. Probá de nuevo dentro de un rato y con gusto te ayudo. Hasta entonces, en la guía vas a encontrar muchas respuestas. | Assistant: when it keeps failing |
| The Assistant is not available here yet. The guide and the (?) next to each title can help in the meantime. | Por ahora el Asistente no está disponible acá. Mientras tanto, la guía y el (?) junto a cada título pueden ayudarte. | Assistant: when not available |
| hello\|hi\|hey\|good morning\|good afternoon\|good evening | hola\|buenas\|buen dia\|buenos dias\|buenas tardes\|buenas noches | Assistant: words it recognizes, not shown |
| thanks\|thank you\|thanks a lot\|thank you very much\|many thanks | gracias\|muchas gracias\|mil gracias\|muchisimas gracias | Assistant: words it recognizes, not shown |
| You left the conversation. | Saliste de la conversación. | Message in the chat (conversation left) |
| Only you | Solo vos | Messages: a conversation with nobody else |
| Community | Comunidad | Messages: the group with everyone |
| You | Vos | Messages (chat): beside your own name (also Platform console: Superadmins) |
| Earlier messages | Mensajes anteriores | Messages (chat): button |
| Start the conversation | Empezar la conversación | Messages (chat): button |
| Start the group | Crear el grupo | Messages (chat): button |
| Delete this message? | ¿Borrar este mensaje? | Messages (chat): confirmation title |
| Leave {name}? | ¿Salir de {name}? | Messages (chat): confirmation title |
| That didn't work. Try again. | No funcionó. Probá de nuevo. | Messages (chat): dialog text |
| Building committee, Block A… | Comisión de obra, Bloque A… | Messages (chat): example in the group name field |
| One person, or several for a group. | Una persona, o varias para un grupo. | Messages (chat): hint |
| Chats | Chats | Messages (chat): label |
| Group name | Nombre del grupo | Messages (chat): label |
| Groups | Grupos | Messages (chat): label |
| Show me around | Mostrame cómo es | Messages (chat): label |
| With | Con | Messages (chat): label |
| All conversations | Todas las conversaciones | Messages (chat): read by screen readers |
| Conversation options | Opciones de la conversación | Messages (chat): read by screen readers |
| Conversations | Conversaciones | Messages (chat): read by screen readers |
| Delete this message | Borrar este mensaje | Messages (chat): read by screen readers |
| Search conversations | Buscar conversaciones | Messages (chat): read by screen readers |
| Send | Enviar | Messages (chat): read by screen readers |
| The Assistant is writing | El Asistente está escribiendo | Messages (chat): read by screen readers |
| Write a message | Escribí un mensaje | Messages (chat): read by screen readers |
| Minimize | Minimizar | Messages (chat): read by screen readers; Messages button in the header: text |
| Unread | Sin leer | Messages (chat): read by screen readers; Notifications page: text |
| Add people | Sumar personas | Messages (chat): text |
| Hello! I am the Assistant of this app, an automatic helper. I can explain how things work and take you where you need to go. What would you like to know? | ¡Hola! Soy el Asistente de la aplicación, una ayuda automática. Puedo explicarte cómo funcionan las cosas y llevarte adonde necesites. ¿Qué querés saber? | Messages (chat): text |
| Leave the group | Salir del grupo | Messages (chat): text |
| New conversation | Nueva conversación | Messages (chat): text |
| No messages yet | Todavía no hay mensajes | Messages (chat): text |
| No one-on-one chats yet. | Todavía no hay chats individuales. | Messages (chat): text |
| Nobody else in the project goes by that name | No hay nadie más en el proyecto con ese nombre | Messages (chat): text |
| Pick a conversation, or start a new one. | Elegí una conversación o empezá una nueva. | Messages (chat): text |
| Rename the group | Cambiar el nombre del grupo | Messages (chat): text |
| Say hello: the first message starts the conversation. | Saludá: el primer mensaje empieza la conversación. | Messages (chat): text |
| Today | Hoy | Messages (chat): text |
| Yesterday | Ayer | Messages (chat): text |
| Messages | Mensajes | Messages (chat): text; Messages button in the header: read by screen readers; Messages page: title; The project's menu: label |
| Assistant | Asistente | Messages (chat): text; Push notice of a new message |
| You will no longer see its messages. | Vas a dejar de ver sus mensajes. | Messages (chat): title |
| Messages: {count} conversation with something new | Mensajes: {count} conversación con novedades | Messages button in the header: text |
| Messages: {count} conversations with something new | Mensajes: {count} conversaciones con novedades | Messages button in the header: text |
| Enter sends. Shift and Enter start a new line. The others see it at once. | Enter envía. Mayúscula y Enter pasan a otra línea. Los demás lo ven al instante. | Messages page: guided tour, step text |
| Pick one person for a private conversation, or several for a group with its name. | Elegí a una persona para una conversación privada, o a varias para un grupo con su nombre. | Messages page: guided tour, step text |
| The latest first; a dot marks those with something new. Nobody else reads them. | Las más recientes primero; un punto marca las que tienen algo nuevo. Nadie más las lee. | Messages page: guided tour, step text |
| Talk with anyone in the project | Conversá con cualquiera del proyecto | Messages page: guided tour, step title |
| Writing | Escribir | Messages page: guided tour, step title |
| Your conversations | Tus conversaciones | Messages page: guided tour, step title |
| Conversations with the people of the project, one on one or in groups. | Conversaciones con la gente del proyecto, de a dos o en grupo. | Messages page: hint |
| Only those in a conversation read it: nobody else, the admins included. For what concerns everyone, the forum is the place. | Solo quienes están en una conversación la leen: nadie más, tampoco los administradores. Para lo que es de todos, está el foro. | Messages page: text |
| New message | Mensaje nuevo | Push notice of a new message: text |
| New message from {name} | Mensaje nuevo de {name} | Push notice of a new message: text |
| Notifications | Notificaciones | Many pages: Notifications bell, Notifications page, Your profile page and others |
| All notifications are marked as read. | Todas las notificaciones quedaron como leídas. | Message on notifications (notifications read) |
| :name asks to join the :family family. | :name pide sumarse a la familia :family. | Notification (bell and phone), a family: text |
| The :family family declined your request. | La familia :family rechazó tu pedido. | Notification (bell and phone), a family: text |
| The :family family has nobody in it and nobody invited, so it was archived. Invite someone into it to bring it back. | La familia :family no tiene a nadie ni invitaciones abiertas, así que se archivó. Invitá a alguien para recuperarla. | Notification (bell and phone), a family: text |
| The :family family has nobody left, so your request closed. Look for yours, or create it. | La familia :family se quedó sin nadie, así que tu pedido se cerró. Buscá la tuya, o creala. | Notification (bell and phone), a family: text |
| A family is gone | Una familia ya no está | Notification (bell and phone), a family: title |
| A family was left empty | Una familia quedó vacía | Notification (bell and phone), a family: title |
| Someone asks to join your family | Alguien pide sumarse a tu familia | Notification (bell and phone), a family: title |
| Your request was declined | Rechazaron tu pedido | Notification (bell and phone), a family: title |
| :title is on the calendar. | :title ya está en la agenda. | Notification (bell and phone), a new event: text |
| New event | Nuevo evento | Notification (bell and phone), a new event: title |
| :name is unlimited now. | :name ya es ilimitado. | Notification (bell and phone), a project: text |
| :name was created. | Se creó :name. | Notification (bell and phone), a project: text |
| New project | Nuevo proyecto | Notification (bell and phone), a project: title |
| Your project is unlimited | Tu proyecto es ilimitado | Notification (bell and phone), a project: title |
| :requester asks you to approve :name. | :requester te pide que apruebes :name. | Notification (bell and phone), a project asking approval: text |
| A project asks for approval | Un proyecto pide aprobación | Notification (bell and phone), a project asking approval: title |
| :name asks to join :project. | :name pide sumarse a :project. | Notification (bell and phone), a request to join: text |
| The admins of :project accepted your request. | Los administradores de :project aceptaron tu pedido. | Notification (bell and phone), a request to join: text |
| The admins of :project did not accept your request. | Los administradores de :project no aceptaron tu pedido. | Notification (bell and phone), a request to join: text |
| Someone asks to join | Alguien pide sumarse | Notification (bell and phone), a request to join: title |
| You are in | Ya estás adentro | Notification (bell and phone), a request to join: title |
| Your request was not accepted | No aceptaron tu pedido | Notification (bell and phone), a request to join: title |
| :name added you to the :family family in :project. | :name te sumó a la familia :family en :project. | Notification (bell and phone), added to a family: text |
| You are in a family | Ya estás en una familia | Notification (bell and phone), added to a family: title |
| :name invites you to join :project. | :name te invita a sumarte a :project. | Notification (bell and phone), an invitation: text |
| You are invited to a project | Te invitaron a un proyecto | Notification (bell and phone), an invitation: title |
| :count families were added by :name. | :name agregó :count familias. | Notification (bell and phone), new families: text |
| A family was added by :name. | :name agregó una familia. | Notification (bell and phone), new families: text |
| New families | Nuevas familias | Notification (bell and phone), new families: title |
| :name shared :count files. | :name compartió :count archivos. | Notification (bell and phone), new files: text |
| :name shared a file. | :name compartió un archivo. | Notification (bell and phone), new files: text |
| New in the files | Novedades en los archivos | Notification (bell and phone), new files: title |
| :name added :count units. | :name agregó :count viviendas. | Notification (bell and phone), new units: text |
| :name added a unit. | :name agregó una vivienda. | Notification (bell and phone), new units: text |
| New units | Nuevas viviendas | Notification (bell and phone), new units: title |
| :name joined the :family family. | :name se sumó a la familia :family. | Notification (bell and phone), someone joined: text |
| :name joined the project. | :name se sumó al proyecto. | Notification (bell and phone), someone joined: text |
| Someone joined | Alguien se sumó | Notification (bell and phone), someone joined: title |
| :name opened «:title». | :name abrió «:title». | Notification (bell and phone), the forum: text |
| :name replied in «:title». | :name respondió en «:title». | Notification (bell and phone), the forum: text |
| A reply in the forum | Una respuesta en el foro | Notification (bell and phone), the forum: title |
| New topic in the forum | Nuevo tema en el foro | Notification (bell and phone), the forum: title |
| :name invalidated the lottery: ":reason". It will run again. | :name invalidó el sorteo: «:reason». Se va a volver a hacer. | Notification (bell and phone), the lottery: text |
| Every family has its unit. See the results. | Cada familia tiene su vivienda. Mirá los resultados. | Notification (bell and phone), the lottery: text |
| See what happened on the lottery page. It can run again. | Mirá en la página del sorteo qué pasó. Se puede volver a hacer. | Notification (bell and phone), the lottery: text |
| The lottery could not finish | El sorteo no pudo terminar | Notification (bell and phone), the lottery: title |
| The lottery is done | El sorteo terminó | Notification (bell and phone), the lottery: title |
| The lottery was invalidated | Se invalidó el sorteo | Notification (bell and phone), the lottery: title |
| Notifications, {count} unread | Notificaciones, {count} sin leer | Notifications bell: read by screen readers |
| See all notifications | Ver todas las notificaciones | Notifications bell: text |
| Mark all as read | Marcar todo como leído | Notifications bell: text; Notifications page |
| No notifications yet | Todavía no hay notificaciones | Notifications bell: text; Notifications page: title |
| Read | Leída | Notifications page: filter of notifications |
| Newest first, unread ones marked. Opening one takes you where it happened and marks it read. | Las más nuevas primero, las no leídas marcadas. Al abrir una, vas adonde pasó y queda leída. | Notifications page: guided tour, step text |
| Show only the unread ones, or mark them all as read at once. | Mostrá solo las no leídas, o marcalas todas como leídas de una vez. | Notifications page: guided tour, step text |
| Only what is new | Solo lo nuevo | Notifications page: guided tour, step title |
| What happened | Lo que pasó | Notifications page: guided tour, step title |
| Only unread | Solo sin leer | Notifications page: text |
| Show all | Ver todas | Notifications page: text |
| What happened in your projects, newest first. The bell shows the latest. Open one to go there. | Lo que pasó en tus proyectos, lo más nuevo primero. La campana muestra lo último. Abrí una para ir ahí. | Notifications page: text |
| Nothing unread | Nada sin leer | Notifications page: title |
| Phone | Teléfono | Downloaded spreadsheets, admins: text; Your profile page: label |
| People who take part in everything but the lottery, until they join their family. Without guests, whoever enters joins their family first. | Personas que participan en todo menos en el sorteo, hasta que se sumen a su familia. Sin invitados, quien entra primero se suma a su familia. | Project options, in settings and when starting a project: hint |
| Allow guests | Permitir invitados | Project options, in settings and when starting a project: label |
| Anyone, at once | Cualquiera, en el momento | Project options, in settings and when starting a project: label |
| Chat | Chat | Project options, in settings and when starting a project: label |
| Chat assistant | Asistente del chat | Project options, in settings and when starting a project: label |
| Files (documents and recordings) | Archivos (documentos y grabaciones) | Project options, in settings and when starting a project: label |
| Gallery (photos and videos) | Galería (fotos y videos) | Project options, in settings and when starting a project: label |
| Show it in Find a project | Mostrarlo en Encontrar un proyecto | Project options, in settings and when starting a project: label |
| Those the admins accept | Quienes acepten los administradores | Project options, in settings and when starting a project: label |
| Who can join | Quién puede sumarse | Project options, in settings and when starting a project: label |
| Anyone with an account can find it, and read its name and description. | Cualquiera con una cuenta puede encontrarlo y leer su nombre y descripción. | Project options, in settings and when starting a project: text |
| It shows there once the project is approved. | Aparece ahí cuando el proyecto esté aprobado. | Project options, in settings and when starting a project: text |
| What it has | Qué incluye | Project options, in settings and when starting a project: text |
| Delete the project | Borrar el proyecto | Project settings page, admins: button; Platform console: UI Lab samples |
| Delete {project}? | ¿Borrar {project}? | Project settings page, admins: confirmation title |
| Everything in it goes: families, units, files, the lottery and its records. This cannot be undone. | Se borra todo: familias, viviendas, archivos, el sorteo y sus registros. No se puede deshacer. | Project settings page, admins: dialog text |
| Its name, description and organization appear to everyone in the project. | Su nombre, descripción y organización los ve todo el proyecto. | Project settings page, admins: guided tour, step text |
| Its people, families and units, as spreadsheets to keep or share. | Su gente, sus familias y sus viviendas, como planillas para guardar o compartir. | Project settings page, admins: guided tour, step text |
| Listed, others can find the project and join it: at once, or when the admins accept them. Then they join their family. | Si lo mostrás, otros pueden encontrar el proyecto y sumarse: en el momento, o cuando los administradores los acepten. Después se suman a su familia. | Project settings page, admins: guided tour, step text |
| Removes the project and everything in it, for everyone. You are asked to type its name first. | Quita el proyecto y todo lo que tiene, para todos. Antes te pedimos que escribas su nombre. | Project settings page, admins: guided tour, step text |
| Can be found | Se puede encontrar | Project settings page, admins: guided tour, step title |
| Deleting it | Borrarlo | Project settings page, admins: guided tour, step title |
| Its lists | Sus listas | Project settings page, admins: guided tour, step title |
| What people see | Lo que ve la gente | Project settings page, admins: guided tour, step title |
| Download its lists | Descargar sus listas | Project settings page, admins: heading |
| Its name, what it is about, and whether others can find it. | Su nombre, de qué se trata y si otros pueden encontrarlo. | Project settings page, admins: hint |
| As spreadsheets, to open in Excel, LibreOffice or Google Sheets. | Como planillas, para abrir en Excel, LibreOffice o Google Sheets. | Project settings page, admins: text |
| Its name, what it is about, whether others can find it; and, with care, deleting it. | Su nombre, de qué se trata, si otros pueden encontrarlo; y, con cuidado, borrarlo. | Project settings page, admins: text |
| Removes the project and everything in it, for everyone. | Borra el proyecto y todo lo que tiene, para todos. | Project settings page, admins: text |
| Project settings | Configuración del proyecto | Project settings page, admins: title |
| Setup | Puesta a punto | Tabs of settings: label |
| Project | Proyecto | Tabs of settings: label; Platform console: Activity: text |
| {count} waiting for an answer | {count} sin responder | Tabs of settings: read by screen readers |
| Settings | Configuración | Tabs of settings: read by screen readers; The project's menu: label; Platform console menu: label; Platform console: Settings: title |
| Your email address was changed. | Tu correo cambió. | Message about your email (email changed) |
| We sent a confirmation link to :email. Your address changes once you confirm it. | Te mandamos un enlace de confirmación a :email. Tu correo cambia cuando lo confirmes. | Message about your email (email verification sent) |
| Your email address is confirmed. Welcome. | Tu correo quedó confirmado. Te damos la bienvenida. | Message about your email (email verified) |
| Your picture was changed. | Tu foto cambió. | Message on your profile (avatar updated) |
| Your profile was saved. | Tu perfil quedó guardado. | Message on your profile (profile updated) |
| About you: your name and picture, as others see them. Password: how you sign in. Preferences: your language and the look of the app. | Sobre vos: tu nombre y tu foto, como los ven los demás. Contraseña: cómo entrás. Preferencias: tu idioma y el aspecto de la aplicación. | Your profile page: guided tour, step text |
| People recognize you by it across your projects. Pick a photo from your device. | Con ella te reconocen en todos tus proyectos. Elegí una foto de tu dispositivo. | Your profile page: guided tour, step text |
| Three parts | Tres partes | Your profile page: guided tour, step title |
| Your picture | Tu foto | Your profile page: guided tour, step title |
| Appearance | Apariencia | Your profile page: heading |
| Help | Ayuda | Your profile page: heading |
| Everything about you: how people see you, how you sign in, your language and how the app looks. | Todo lo tuyo: cómo te ven los demás, cómo entrás, tu idioma y cómo se ve la aplicación. | Your profile page: hint |
| Needed to change your email | Hace falta para cambiar tu correo | Your profile page: hint |
| Only admins see it | Solo la ven los administradores | Your profile page: hint |
| Show my email to the people in my projects | Mostrar mi correo a la gente de mis proyectos | Your profile page: label |
| Show my phone to the people in my projects | Mostrar mi teléfono a la gente de mis proyectos | Your profile page: label |
| Your password | Tu contraseña | Your profile page: label; Delete your account, in your profile |
| Join your family | Sumarte a tu familia | Your profile page: link; The project's menu: label |
| About you | Sobre vos | Your profile page: text |
| Change picture | Cambiar la foto | Your profile page: text |
| Changes are confirmed by email | Los cambios se confirman por correo | Your profile page: text |
| Confirm {email} to change it | Confirmá {email} para cambiarlo | Your profile page: text |
| Only on this device. | Solo en este dispositivo. | Your profile page: text |
| Preferences | Preferencias | Your profile page: text |
| The admins of your projects always do. Everyone else, if you share them. | Los administradores de tus proyectos siempre los ven. Los demás, si los compartís. | Your profile page: text |
| Who sees your email and phone | Quién ve tu correo y tu teléfono | Your profile page: text |
| How others see you, how you sign in, your language and the look of the app. | Cómo te ven los demás, cómo entrás, tu idioma y el aspecto de la aplicación. | Your profile page: title |
| Your account | Tu cuenta | Your profile page: title; The account menu: label; The app's frame: menu and header: read by screen readers |
| Profile | Perfil | Your profile page: title; The account menu: label; The app's frame: menu and header: text |
| Forest | Bosque | Your preferences: colour names: label |
| Indigo | Índigo | Your preferences: colour names: label |
| Ocean | Océano | Your preferences: colour names: label |
| Terracotta | Terracota | Your preferences: colour names: label |
| Each page's tour on a first visit. The ? by its title plays it anytime. | El recorrido de cada página en tu primera visita. El ? junto al título lo muestra cuando quieras. | Your preferences page and the header's quick settings: hint |
| What happens in your projects, even with the app closed. | Lo que pasa en tus proyectos, aunque la aplicación esté cerrada. | Your preferences page and the header's quick settings: hint |
| Guided tours | Recorridos guiados | Your preferences page and the header's quick settings: label |
| Large | Grande | Your preferences page and the header's quick settings: label |
| Light or dark | Claro u oscuro | Your preferences page and the header's quick settings: label |
| Medium | Mediano | Your preferences page and the header's quick settings: label |
| Notifications on this device | Notificaciones en este dispositivo | Your preferences page and the header's quick settings: label |
| Small | Chico | Your preferences page and the header's quick settings: label |
| Text size | Tamaño del texto | Your preferences page and the header's quick settings: label |
| Automatic | Automático | Your preferences page and the header's quick settings: label; Platform console: UI Lab |
| Dark | Oscuro | Your preferences page and the header's quick settings: label; Platform console: UI Lab |
| Light | Claro | Your preferences page and the header's quick settings: label; Platform console: UI Lab; Platform console: UI Lab samples |
| This browser cannot show notifications. | Este navegador no puede mostrar notificaciones. | Your preferences page and the header's quick settings: message |
| Contrast | Contraste | Your preferences page and the header's quick settings: text |
| Notifications are blocked for this site: allow them in the browser settings. | Las notificaciones están bloqueadas para este sitio: permitilas en la configuración del navegador. | Your preferences page and the header's quick settings: text |
| On every device, and in the emails you receive. | En todos tus dispositivos, y en los correos que recibís. | Your preferences page and the header's quick settings: text |
| On iPhone and iPad, add the app to the home screen first, then turn them on from there. | En iPhone y iPad, primero agregá la aplicación a la pantalla de inicio y activalas desde ahí. | Your preferences page and the header's quick settings: text |
| Stronger lines and text, no transparency. | Líneas y textos más marcados, sin transparencias. | Your preferences page and the header's quick settings: text |
| High contrast | Alto contraste | Your preferences page and the header's quick settings: text; Platform console: UI Lab: label |
| Colours | Colores | Your preferences page and the header's quick settings: text; Platform console: UI Lab: read by screen readers |
| That password is not right. | Esa contraseña no es correcta. | Asking your password: wrong password |
| Your password was changed. | Tu contraseña cambió. | Message on your password (password updated) |
| Current password | Contraseña actual | Password tab of your profile: label |
| Repeat the new password | Repetí la contraseña nueva | Password tab of your profile: label |
| Change the password | Cambiar la contraseña | Password tab of your profile: text |
| Email me a link to choose a password | Mandame un enlace para elegir una contraseña | Password tab of your profile: text |
| Delete my account | Borrar mi cuenta | Delete your account, in your profile: button |
| Delete your account? | ¿Borrar tu cuenta? | Delete your account, in your profile: confirmation title |
| You leave every project, and your profile is gone. What you shared stays with the projects, without your name. This cannot be undone. | Salís de todos los proyectos y tu perfil desaparece. Lo que compartiste queda en los proyectos, sin tu nombre. No se puede deshacer. | Delete your account, in your profile: dialog text |
| Delete your account | Borrar tu cuenta | Delete your account, in your profile: heading |
| You leave every project. What you shared stays with them, without your name. | Salís de todos los proyectos. Lo que compartiste queda en ellos, sin tu nombre. | Delete your account, in your profile: text |
| Choose a new password | Elegir una contraseña nueva | Email to choose a new password: button |
| Choose a new password | Elegí una contraseña nueva | Email to choose a new password: subject |
| If it was not you, ignore this email. Your password stays as it is. | Si no fuiste vos, ignorá este correo. Tu contraseña sigue igual. | Email to choose a new password: text |
| Someone asked to change the password of your account. | Alguien pidió cambiar la contraseña de tu cuenta. | Email to choose a new password: text |
| The link is valid for :minutes minutes. | El enlace vale por :minutes minutos. | Email to choose a new password: text |
| Confirm my email | Confirmar mi correo | Email to confirm an address: button |
| Confirm your email address | Confirmá tu correo | Email to confirm an address: subject |
| Confirm this is your email address. | Confirmá que este es tu correo. | Email to confirm an address: text |
| Confirm this is your new email address. Your account keeps using the old one until you do. | Confirmá que este es tu correo nuevo. Tu cuenta sigue usando el anterior hasta que lo hagas. | Email to confirm an address: text |
| If you did not create an account, ignore this email. | Si no creaste una cuenta, ignorá este correo. | Email to confirm an address: text |
| Your account's email is changing | El correo de tu cuenta va a cambiar | Email to the old address when it is changing: subject |
| If it was not you, change your password now: your account keeps this address until then. | Si no fuiste vos, cambiá tu contraseña ahora. Hasta que se confirme, tu cuenta sigue con este correo. | Email to the old address when it is changing: text |
| Someone asked to change your account's email address to :email. It changes once that address confirms it. | Alguien pidió cambiar el correo de tu cuenta a :email. Cambia cuando se confirme desde esa dirección. | Email to the old address when it is changing: text |
| See the errors | Ver los errores | Email to the owner about a new error: button |
| Something went wrong in :app | Algo falló en :app | Email to the owner about a new error: subject |
| The app ran into an error it had not seen before: | La aplicación tuvo un error que no había visto antes: | Email to the owner about a new error: text |
| Answer | Responder | Email when someone asks to join a family: button |
| :name asks to join the :family family | :name pide sumarse a la familia :family | Email when someone asks to join a family: subject |
| :name asks to join the :family family in :project. Anyone in the family can answer. | :name pide sumarse a la familia :family en :project. Cualquiera de la familia puede responder. | Email when someone asks to join a family: text |
| Open the project | Abrir el proyecto | Email when someone is let in or added to a family: button |
| You are in :project | Ya estás en :project | Email when someone is let in or added to a family: subject |
| You are in the :family family | Ya estás en la familia :family | Email when someone is let in or added to a family: subject |
| :name added you to the :family family in :project. There is nothing else to do. | :name te sumó a la familia :family en :project. No tenés que hacer nada más. | Email when someone is let in or added to a family: text |
| The admins of :project accepted your request: you are in. Create your family there, or someone in it can add you. | Los administradores de :project aceptaron tu pedido: ya estás adentro. Podés crear tu familia ahí, o alguien de ella te puede sumar. | Email when someone is let in or added to a family: text |
| Regards, | Saludos, | Emails: closing of system emails |
| All rights reserved. | Todos los derechos reservados. | Emails: footer of emails |
| If you're having trouble clicking the ":actionText" button, copy and paste the URL below into your web browser: | Si el botón «:actionText» no funciona, copiá y pegá esta dirección en tu navegador: | Emails: footer of emails |
| Hello! | ¡Hola! | Emails: greeting of system emails |
| Whoops! | ¡Ups! | Emails: heading of emails with an error |
| Hello :name! | ¡Hola, :name! | Greeting of every email: text |
| Hello! | ¡Hola! | Greeting of every email: text |
| Accept the invitation | Aceptar la invitación | Invitation email: button |
| You are invited to :project | Te invitan a :project | Invitation email: subject |
| :inviter invites you to help manage :project, as a member of the :family family. | :inviter te invita a ayudar a gestionar :project, como integrante de la familia :family. | Invitation email: text |
| :inviter invites you to help manage :project. | :inviter te invita a ayudar a gestionar :project. | Invitation email: text |
| :inviter invites you to join :project. | :inviter te invita a sumarte a :project. | Invitation email: text |
| :inviter invites you to join the :family family in :project. | :inviter te invita a sumarte a la familia :family en :project. | Invitation email: text |
| If you were not expecting it, you can ignore this email. | Si no la esperabas, podés ignorar este correo. | Invitation email: text |
| The invitation is valid until :date. | La invitación vale hasta el :date. | Invitation email: text |
| Quick actions | Acciones rápidas | Quick actions menu, admins: read by screen readers |
| Upload files | Subir archivos | Quick actions menu, admins: text |
| Upload photos | Subir fotos | Quick actions menu, admins: text |
| Upload photos or files | Subir fotos o archivos | Quick actions menu, admins: text |
| Main | Principal | The app's frame: menu and header: read by screen readers |
| Menu | Menú | The app's frame: menu and header: read by screen readers |
| Navigation | Navegación | The app's frame: menu and header: read by screen readers; Platform console: UI Lab samples: title |
| All your projects | Todos tus proyectos | The app's frame: menu and header: text |
| Questions and answers | Preguntas frecuentes | The app's frame: menu and header: text |
| Skip to content | Saltar al contenido | The app's frame: menu and header: text |
| Guide | Guía | The app's frame: menu and header: text; Guide and questions pages: title |
| Platform console | Consola de la plataforma | The app's frame: menu and header: text; Platform console menu |
| Home | Inicio | The project's menu: label; Platform console: UI Lab samples |
| Fold | Plegar | Fold button of panels: read by screen readers |
| Unfold | Desplegar | Fold button of panels: read by screen readers |
| Keep showing them | Seguir mostrándolos | Guided tour controls: button |
| No more tours | No más recorridos | Guided tour controls: button |
| Done | Listo | Guided tour controls: button; Platform console: Deployments: text |
| No more tours. You can turn them back on in Preferences. | Listo, no más recorridos. Podés volver a activarlos en Preferencias. | Guided tour controls: dialog text |
| End the tour | Terminar el recorrido | Guided tour controls: read by screen readers |
| Show no more tours? | ¿No mostrar más recorridos? | Guided tour controls: read by screen readers |
| Tours | Recorridos | Guided tour controls: read by screen readers |
| Back | Atrás | Guided tour controls: text |
| Step {n} of {total} | Paso {n} de {total} | Guided tour controls: text |
| You can always take one from the ? by each page's title. | Siempre podés hacer uno desde el ? junto al título de cada página. | Guided tour controls: text |
| Tell me more | Contame más | Guided tours: guided tour, step title; Help (?) next to titles: text |
| Got it | Entendido | Help (?) next to titles: button |
| About: {topic} | Ayuda: {topic} | Help (?) next to titles: read by screen readers |
| You see what was last loaded; changes wait until the connection is back. | Ves lo último que se cargó; los cambios esperan a que vuelva la conexión. | Notice when offline: label |
| Offline | Sin conexión | Notice when offline: text |
| Nothing was sent. Try again when the connection is back. | No se mandó nada. Probá de nuevo cuando vuelva la conexión. | Notice when offline: title |
| You are offline | Estás sin conexión | Notice when offline: title |
| Confirm | Confirmar | All forms and dialogs: button |
| Remove this family? | ¿Quitar esta familia? | All forms and dialogs: confirmation title; Platform console: UI Lab samples |
| Reason | Motivo | All forms and dialogs: label |
| Type {text} to confirm | Escribí {text} para confirmar | All forms and dialogs: label; Platform console: Projects |
| Fields marked with * are required. | Los campos con * son obligatorios. | All forms and dialogs: text |
| Show archived ({count}) | Ver archivados ({count}) | Archived items, lists for admins: label |
| Restore | Restaurar | Archived items, lists for admins: text; Platform console: Projects: dialog text |
| Archived | Archivado | Archived items, lists for admins: text; Platform console: Projects: label |
| Nothing archived here | Nada archivado acá | Archived items, lists for admins: title |
| The :attribute must be between :min and :max. | «:attribute» tiene que estar entre :min y :max. | Form error: between.numeric |
| The :attribute must have between :min and :max characters. | «:attribute» tiene que tener entre :min y :max caracteres. | Form error: between.string |
| The two :attribute boxes do not match. Try again. | Las dos casillas de «:attribute» no coinciden. Probá de nuevo. | Form error: confirmed |
| The password is incorrect. | Esa contraseña no es correcta. | Form error: current password |
| The :attribute is not a valid date. Check it and try again. | «:attribute» no es una fecha válida. Revisala y probá de nuevo. | Form error: date |
| The :attribute does not look like an email address. Check it and try again. | «:attribute» no parece un correo. Revisalo y probá de nuevo. | Form error: email |
| Choose one of the options for :attribute. | Elegí una de las opciones de «:attribute». | Form error: enum |
| Choose one of the options for :attribute. | Elegí una de las opciones de «:attribute». | Form error: exists |
| The :attribute must be an image. | «:attribute» tiene que ser una imagen. | Form error: image |
| Choose one of the options for :attribute. | Elegí una de las opciones de «:attribute». | Form error: in |
| The :attribute must be a whole number. | «:attribute» tiene que ser un número entero. | Form error: integer |
| The :attribute is too large. It can be up to :max KB. | «:attribute» es demasiado grande. Puede tener hasta :max KB. | Form error: max.file |
| The :attribute can have up to :max characters. | «:attribute» puede tener hasta :max caracteres. | Form error: max.string |
| The :attribute must be one of these kinds of file: :values. | «:attribute» tiene que ser de uno de estos tipos: :values. | Form error: mimes |
| The :attribute must be one of these kinds of file: :values. | «:attribute» tiene que ser de uno de estos tipos: :values. | Form error: mimetypes |
| The :attribute needs at least :min characters. | «:attribute» necesita al menos :min caracteres. | Form error: min.string |
| The :attribute must be a number. | «:attribute» tiene que ser un número. | Form error: numeric |
| Check the :attribute: it is not written the way it should be. | Revisá «:attribute»: no está bien escrito. | Form error: regex |
| Fill in the :attribute. | Completá «:attribute». | Form error: required |
| That :attribute is already in use. | «:attribute» ya está en uso. | Form error: unique |
| The :attribute is not a valid link. Check it and try again. | «:attribute» no es un enlace válido. Revisalo y probá de nuevo. | Form error: url |
| This account is blocked: it cannot be invited. | Esta cuenta está bloqueada: no se la puede invitar. | Form error on a specific action: account blocked |
| Not your own account, nor a superadmin's: their role goes first. | Ni tu propia cuenta ni la de un superadministrador: primero se le quita el rol. | Form error on a specific action: account protected |
| This person already takes part in the project in that role. | Esta persona ya participa del proyecto con ese rol. | Form error on a specific action: already in project |
| You cannot remove your own superadmin role. | No podés quitarte tu propio rol de superadministrador. | Form error on a specific action: cannot revoke self |
| You are in :max groups, the most there can be: leave one to start another. | Estás en :max grupos, el máximo. Salí de alguno para crear otro. | Form error on a specific action: chat group limit |
| :name is in :max groups, the most there can be. | :name está en :max grupos, el máximo. | Form error on a specific action: chat group limit other |
| Choose people of the project to talk with. | Elegí personas del proyecto para conversar. | Form error on a specific action: chat people |
| You have changed your email several times lately: try again in a while. | Cambiaste tu correo varias veces hace poco. Probá de nuevo más tarde. | Form error on a specific action: email changes limit |
| Another account already uses this email. | Otra cuenta ya usa este correo. | Form error on a specific action: email taken |
| You are already in this family. | Ya estás en esta familia. | Form error on a specific action: family already in |
| Here everyone takes part as a family: ask to join yours, or create it. | Acá todos participan en familia. Pedí sumarte a la tuya, o creala. | Form error on a specific action: family cannot leave |
| This family declined your request. | Esta familia rechazó tu pedido. | Form error on a specific action: family declined |
| This family declined this person: only someone in it can lift that. | Esta familia rechazó a esta persona. Solo alguien de la familia puede levantar el rechazo. | Form error on a specific action: family declined them |
| This family already received a unit in the lottery: it cannot be removed. | Esta familia ya recibió una vivienda en el sorteo: no se puede quitar. | Form error on a specific action: family has unit |
| You have not joined a family here. | Todavía no te sumaste a tu familia acá. | Form error on a specific action: family none |
| You are already waiting for a family to answer: cancel that first. | Ya estás esperando la respuesta de una familia. Cancelá ese pedido primero. | Form error on a specific action: family request open |
| This project has this kind of file turned off. | Este proyecto tiene desactivado este tipo de archivo. | Form error on a specific action: feature off |
| Guests remain (:count, counting invitations on their way): give them a family or remove them first. | Quedan invitados (:count, contando invitaciones en camino). Primero sumalos a una familia o quitalos. | Form error on a specific action: guests remain |
| Images up to :max megapixels can be shared; this one has more. | Se pueden compartir imágenes de hasta :max megapíxeles. Esta tiene más. | Form error on a specific action: image too large |
| A project needs at least one admin. | Un proyecto necesita al menos un administrador. | Form error on a specific action: last admin |
| For now this project has room for up to :max families: ask for unlimited to lift it. | Por ahora este proyecto tiene lugar para hasta :max familias. Pedí ilimitado para quitar el límite. | Form error on a specific action: limit families |
| For now this project can send up to :max invitations a day: ask for unlimited to lift it. | Por ahora este proyecto puede mandar hasta :max invitaciones por día. Pedí ilimitado para quitar el límite. | Form error on a specific action: limit invitations |
| For now this project has room for up to :max people: ask for unlimited to lift it. | Por ahora este proyecto tiene lugar para hasta :max personas. Pedí ilimitado para quitar el límite. | Form error on a specific action: limit members |
| For now this project has room for :max MB of files: ask for unlimited to lift it. | Por ahora este proyecto tiene lugar para :max MB de archivos. Pedí ilimitado para quitar el límite. | Form error on a specific action: limit storage |
| For now this project has room for up to :max units: ask for unlimited to lift it. | Por ahora este proyecto tiene lugar para hasta :max viviendas. Pedí ilimitado para quitar el límite. | Form error on a specific action: limit units |
| Someone who is not an admin must belong to a family. | Quien no es administrador debe pertenecer a una familia. | Form error on a specific action: member needs family |
| There is no account with this email. | No hay ninguna cuenta con este correo. | Form error on a specific action: no account with email |
| You are the only admin of :projects: name another admin first, or delete the project. | Sos el único administrador de :projects. Nombrá a otro administrador primero, o borrá el proyecto. | Form error on a specific action: only admin of |
| The owner of the platform keeps this role. | Quien es dueño de la plataforma conserva este rol. | Form error on a specific action: owner keeps role |
| The owner of the platform keeps their account. | Quien es dueño de la plataforma conserva su cuenta. | Form error on a specific action: owner stays |
| Only a unit's own shape is a unit: add units from the plan's palette or the units page. | Solo la forma de una vivienda es una vivienda. Agregá viviendas desde la paleta del plano o la página de viviendas. | Form error on a specific action: plan unit items |
| You can only rank units of your family's type. | Solo podés ordenar viviendas del tipo de tu familia. | Form error on a specific action: preferences foreign units |
| This device cannot take notifications from here. | Este dispositivo no puede recibir notificaciones desde acá. | Form error on a specific action: push device |
| This account has not confirmed its email, or is blocked: it cannot have this role. | Esta cuenta no confirmó su correo o está bloqueada: no puede tener este rol. | Form error on a specific action: superadmin unconfirmed |
| This unit was already given to a family in the lottery: it cannot be removed. | Esta vivienda ya fue asignada a una familia en el sorteo: no se puede quitar. | Form error on a specific action: unit assigned |
| Families or units still use this unit type. | Todavía hay familias o viviendas con este tipo. | Form error on a specific action: unit type in use |
| You need to accept the :attribute. | Tenés que aceptar «:attribute». | Form error, standard rule: accepted |
| You need to accept the :attribute when :other is :value. | Tenés que aceptar «:attribute» cuando :other es :value. | Form error, standard rule: accepted if |
| The :attribute field must be a valid URL. | El campo :attribute debe ser una URL válida. | Form error, standard rule: active url |
| The :attribute field must be a date after :date. | El campo :attribute debe ser una fecha posterior a :date. | Form error, standard rule: after |
| The :attribute field must be a date after or equal to :date. | El campo :attribute debe ser una fecha posterior o igual a :date. | Form error, standard rule: after or equal |
| The :attribute field must only contain letters. | El campo :attribute solo debe contener letras. | Form error, standard rule: alpha |
| The :attribute field must only contain letters, numbers, dashes, and underscores. | El campo :attribute solo debe contener letras, números, guiones y guiones bajos. | Form error, standard rule: alpha dash |
| The :attribute field must only contain letters and numbers. | El campo :attribute solo debe contener letras y números. | Form error, standard rule: alpha num |
| The :attribute field is invalid. | El campo :attribute es inválido. | Form error, standard rule: any of |
| The :attribute field must be an array. | El campo :attribute debe ser una lista. | Form error, standard rule: array |
| The :attribute field must only contain single-byte alphanumeric characters and symbols. | El campo :attribute solo debe contener caracteres alfanuméricos y símbolos de un byte. | Form error, standard rule: ascii |
| The :attribute field must be a date before :date. | El campo :attribute debe ser una fecha anterior a :date. | Form error, standard rule: before |
| The :attribute field must be a date before or equal to :date. | El campo :attribute debe ser una fecha anterior o igual a :date. | Form error, standard rule: before or equal |
| The :attribute field must have between :min and :max items. | El campo :attribute debe tener entre :min y :max elementos. | Form error, standard rule: between.array |
| The :attribute field must be between :min and :max kilobytes. | El campo :attribute debe tener entre :min y :max kilobytes. | Form error, standard rule: between.file |
| The :attribute field must be true or false. | El campo :attribute debe ser verdadero o falso. | Form error, standard rule: boolean |
| The :attribute field contains an unauthorized value. | El campo :attribute contiene un valor no autorizado. | Form error, standard rule: can |
| The :attribute field is missing a required value. | El campo :attribute no tiene un valor requerido. | Form error, standard rule: contains |
| The :attribute field must be a date equal to :date. | El campo :attribute debe ser una fecha igual a :date. | Form error, standard rule: date equals |
| The :attribute field must match the format :format. | El campo :attribute debe coincidir con el formato :format. | Form error, standard rule: date format |
| The :attribute field must have :decimal decimal places. | El campo :attribute debe tener :decimal lugares decimales. | Form error, standard rule: decimal |
| You need to decline the :attribute. | Tenés que rechazar «:attribute». | Form error, standard rule: declined |
| You need to decline the :attribute when :other is :value. | Tenés que rechazar «:attribute» cuando :other es :value. | Form error, standard rule: declined if |
| The :attribute field and :other must be different. | El campo :attribute y :other deben ser diferentes. | Form error, standard rule: different |
| The :attribute field must be :digits digits. | El campo :attribute debe tener :digits dígitos. | Form error, standard rule: digits |
| The :attribute field must be between :min and :max digits. | El campo :attribute debe tener entre :min y :max dígitos. | Form error, standard rule: digits between |
| The :attribute field has invalid image dimensions. | El campo :attribute tiene dimensiones de imagen inválidas. | Form error, standard rule: dimensions |
| The :attribute field has a duplicate value. | El campo :attribute tiene un valor duplicado. | Form error, standard rule: distinct |
| The :attribute field must not end with one of the following: :values. | El campo :attribute no debe terminar con uno de los siguientes: :values. | Form error, standard rule: doesnt end with |
| The :attribute field must not start with one of the following: :values. | El campo :attribute no debe comenzar con uno de los siguientes: :values. | Form error, standard rule: doesnt start with |
| The :attribute field must end with one of the following: :values. | El campo :attribute debe terminar con uno de los siguientes: :values. | Form error, standard rule: ends with |
| The :attribute field must have one of the following extensions: :values. | El campo :attribute debe tener una de las siguientes extensiones: :values. | Form error, standard rule: extensions |
| The :attribute field must be a file. | El campo :attribute debe ser un archivo. | Form error, standard rule: file |
| The :attribute field must have a value. | El campo :attribute debe tener un valor. | Form error, standard rule: filled |
| The :attribute field must have more than :value items. | El campo :attribute debe tener más de :value elementos. | Form error, standard rule: gt.array |
| The :attribute field must be greater than :value kilobytes. | El campo :attribute debe ser mayor que :value kilobytes. | Form error, standard rule: gt.file |
| The :attribute field must be greater than :value. | El campo :attribute debe ser mayor que :value. | Form error, standard rule: gt.numeric |
| The :attribute field must be greater than :value characters. | El campo :attribute debe ser mayor que :value caracteres. | Form error, standard rule: gt.string |
| The :attribute field must have :value items or more. | El campo :attribute debe tener :value elementos o más. | Form error, standard rule: gte.array |
| The :attribute field must be greater than or equal to :value kilobytes. | El campo :attribute debe ser mayor o igual a :value kilobytes. | Form error, standard rule: gte.file |
| The :attribute field must be greater than or equal to :value. | El campo :attribute debe ser mayor o igual a :value. | Form error, standard rule: gte.numeric |
| The :attribute field must be greater than or equal to :value characters. | El campo :attribute debe ser mayor o igual a :value caracteres. | Form error, standard rule: gte.string |
| The :attribute field must be a valid hexadecimal color. | El campo :attribute debe ser un color hexadecimal válido. | Form error, standard rule: hex color |
| The :attribute field must exist in :other. | El campo :attribute debe existir en :other. | Form error, standard rule: in array |
| The :attribute field must contain at least one of the following keys: :values. | El campo :attribute debe contener al menos una de las siguientes claves: :values. | Form error, standard rule: in array keys |
| The :attribute field must be a valid IP address. | El campo :attribute debe ser una IP válida. | Form error, standard rule: ip |
| The :attribute field must be a valid IPv4 address. | El campo :attribute debe ser una IPv4 válida. | Form error, standard rule: ipv4 |
| The :attribute field must be a valid IPv6 address. | El campo :attribute debe ser una IPv6 válida. | Form error, standard rule: ipv6 |
| The :attribute field must be a valid JSON string. | El campo :attribute debe ser un JSON válido. | Form error, standard rule: json |
| The :attribute field must be a list. | El campo :attribute debe ser una lista. | Form error, standard rule: list |
| The :attribute field must be lowercase. | El campo :attribute debe estar en minúsculas. | Form error, standard rule: lowercase |
| The :attribute field must have less than :value items. | El campo :attribute debe tener menos de :value elementos. | Form error, standard rule: lt.array |
| The :attribute field must be less than :value kilobytes. | El campo :attribute debe ser menor que :value kilobytes. | Form error, standard rule: lt.file |
| The :attribute field must be less than :value. | El campo :attribute debe ser menor que :value. | Form error, standard rule: lt.numeric |
| The :attribute field must be less than :value characters. | El campo :attribute debe ser menor que :value caracteres. | Form error, standard rule: lt.string |
| The :attribute field must not have more than :value items. | El campo :attribute no debe tener más de :value elementos. | Form error, standard rule: lte.array |
| The :attribute field must be less than or equal to :value kilobytes. | El campo :attribute debe ser menor o igual a :value kilobytes. | Form error, standard rule: lte.file |
| The :attribute field must be less than or equal to :value. | El campo :attribute debe ser menor o igual a :value. | Form error, standard rule: lte.numeric |
| The :attribute field must be less than or equal to :value characters. | El campo :attribute debe ser menor o igual a :value caracteres. | Form error, standard rule: lte.string |
| The :attribute field must be a valid MAC address. | El campo :attribute debe ser una dirección MAC válida. | Form error, standard rule: mac address |
| The :attribute field must not have more than :max digits. | El campo :attribute no debe tener más de :max dígitos. | Form error, standard rule: max digits |
| The :attribute can have up to :max items. | «:attribute» puede tener hasta :max elementos. | Form error, standard rule: max.array |
| The :attribute can be up to :max. | «:attribute» puede ser hasta :max. | Form error, standard rule: max.numeric |
| The :attribute field must have at least :min digits. | El campo :attribute debe tener al menos :min dígitos. | Form error, standard rule: min digits |
| The :attribute needs at least :min items. | «:attribute» necesita al menos :min elementos. | Form error, standard rule: min.array |
| The :attribute field must be at least :min kilobytes. | El campo :attribute debe tener al menos :min kilobytes. | Form error, standard rule: min.file |
| The :attribute must be at least :min. | «:attribute» tiene que ser al menos :min. | Form error, standard rule: min.numeric |
| The :attribute field must be missing. | El campo :attribute debe estar ausente. | Form error, standard rule: missing |
| The :attribute field must be missing when :other is :value. | El campo :attribute debe estar ausente cuando :other es :value. | Form error, standard rule: missing if |
| The :attribute field must be missing unless :other is :value. | El campo :attribute debe estar ausente a menos que :other sea :value. | Form error, standard rule: missing unless |
| The :attribute field must be missing when :values is present. | El campo :attribute debe estar ausente cuando :values está presente. | Form error, standard rule: missing with |
| The :attribute field must be missing when :values are present. | El campo :attribute debe estar ausente cuando :values están presentes. | Form error, standard rule: missing with all |
| The :attribute field must be a multiple of :value. | El campo :attribute debe ser un múltiplo de :value. | Form error, standard rule: multiple of |
| The selected :attribute is invalid. | El :attribute seleccionado es inválido. | Form error, standard rule: not in |
| The :attribute field format is invalid. | El formato del campo :attribute es inválido. | Form error, standard rule: not regex |
| The :attribute field must contain at least one letter. | El campo :attribute debe contener al menos una letra. | Form error, standard rule: password.letters |
| The :attribute field must contain at least one uppercase and one lowercase letter. | El campo :attribute debe contener al menos una letra mayúscula y una minúscula. | Form error, standard rule: password.mixed |
| The :attribute field must contain at least one number. | El campo :attribute debe contener al menos un número. | Form error, standard rule: password.numbers |
| The :attribute field must contain at least one symbol. | El campo :attribute debe contener al menos un símbolo. | Form error, standard rule: password.symbols |
| The given :attribute has appeared in a data leak. Please choose a different :attribute. | Ese :attribute apareció en una filtración de datos. Elegí otro :attribute. | Form error, standard rule: password.uncompromised |
| The :attribute field must be present. | El campo :attribute debe estar presente. | Form error, standard rule: present |
| The :attribute field must be present when :other is :value. | El campo :attribute debe estar presente cuando :other es :value. | Form error, standard rule: present if |
| The :attribute field must be present unless :other is :value. | El campo :attribute debe estar presente a menos que :other sea :value. | Form error, standard rule: present unless |
| The :attribute field must be present when :values is present. | El campo :attribute debe estar presente cuando :values está presente. | Form error, standard rule: present with |
| The :attribute field must be present when :values are present. | El campo :attribute debe estar presente cuando :values están presentes. | Form error, standard rule: present with all |
| The :attribute field is prohibited. | El campo :attribute está prohibido. | Form error, standard rule: prohibited |
| The :attribute field is prohibited when :other is :value. | El campo :attribute está prohibido cuando :other es :value. | Form error, standard rule: prohibited if |
| The :attribute field is prohibited when :other is accepted. | El campo :attribute está prohibido cuando :other es aceptado. | Form error, standard rule: prohibited if accepted |
| The :attribute field is prohibited when :other is declined. | El campo :attribute está prohibido cuando :other es rechazado. | Form error, standard rule: prohibited if declined |
| The :attribute field is prohibited unless :other is in :values. | El campo :attribute está prohibido a menos que :other esté en :values. | Form error, standard rule: prohibited unless |
| The :attribute field prohibits :other from being present. | El campo :attribute prohíbe que :other esté presente. | Form error, standard rule: prohibits |
| The :attribute field must contain entries for: :values. | El campo :attribute debe contener entradas para: :values. | Form error, standard rule: required array keys |
| The :attribute field is required when :other is :value. | El campo :attribute es obligatorio cuando :other es :value. | Form error, standard rule: required if |
| The :attribute field is required when :other is accepted. | El campo :attribute es obligatorio cuando :other es aceptado. | Form error, standard rule: required if accepted |
| The :attribute field is required when :other is declined. | El campo :attribute es obligatorio cuando :other es rechazado. | Form error, standard rule: required if declined |
| The :attribute field is required unless :other is in :values. | El campo :attribute es obligatorio a menos que :other esté en :values. | Form error, standard rule: required unless |
| The :attribute field is required when :values is present. | El campo :attribute es obligatorio cuando :values está presente. | Form error, standard rule: required with |
| The :attribute field is required when :values are present. | El campo :attribute es obligatorio cuando :values están presentes. | Form error, standard rule: required with all |
| The :attribute field is required when :values is not present. | El campo :attribute es obligatorio cuando :values no está presente. | Form error, standard rule: required without |
| The :attribute field is required when none of :values are present. | El campo :attribute es obligatorio cuando ninguno de :values están presentes. | Form error, standard rule: required without all |
| The :attribute field must match :other. | El campo :attribute debe coincidir con :other. | Form error, standard rule: same |
| The :attribute field must contain :size items. | El campo :attribute debe contener :size elementos. | Form error, standard rule: size.array |
| The :attribute field must be :size kilobytes. | El campo :attribute debe tener :size kilobytes. | Form error, standard rule: size.file |
| The :attribute field must be :size. | El campo :attribute debe ser :size. | Form error, standard rule: size.numeric |
| The :attribute field must be :size characters. | El campo :attribute debe tener :size caracteres. | Form error, standard rule: size.string |
| The :attribute field must start with one of the following: :values. | El campo :attribute debe comenzar con uno de los siguientes: :values. | Form error, standard rule: starts with |
| The :attribute field must be a string. | El campo :attribute debe ser un string. | Form error, standard rule: string |
| The :attribute field must be a valid timezone. | El campo :attribute debe ser una zona horaria válida. | Form error, standard rule: timezone |
| The :attribute field must be a valid ULID. | El campo :attribute debe ser un ULID válido. | Form error, standard rule: ulid |
| The :attribute could not be sent. Try again. | No se pudo mandar «:attribute». Probá de nuevo. | Form error, standard rule: uploaded |
| The :attribute field must be uppercase. | El campo :attribute debe estar en mayúsculas. | Form error, standard rule: uppercase |
| The :attribute field must be a valid UUID. | El campo :attribute debe ser un UUID válido. | Form error, standard rule: uuid |
| about | sobre vos | Form errors: name of a field, inside a message |
| confirmation | confirmación | Form errors: name of a field, inside a message |
| current password | contraseña actual | Form errors: name of a field, inside a message |
| description | descripción | Form errors: name of a field, inside a message |
| email | correo | Form errors: name of a field, inside a message |
| end date | fecha de fin | Form errors: name of a field, inside a message |
| family | familia | Form errors: name of a field, inside a message |
| file | archivo | Form errors: name of a field, inside a message |
| files | archivos | Form errors: name of a field, inside a message |
| first name | nombre | Form errors: name of a field, inside a message |
| ID number | cédula | Form errors: name of a field, inside a message |
| last name | apellido | Form errors: name of a field, inside a message |
| location | lugar | Form errors: name of a field, inside a message |
| name | nombre | Form errors: name of a field, inside a message |
| name or number | nombre o número | Form errors: name of a field, inside a message |
| organization | organización | Form errors: name of a field, inside a message |
| password | contraseña | Form errors: name of a field, inside a message |
| phone | teléfono | Form errors: name of a field, inside a message |
| reason | motivo | Form errors: name of a field, inside a message |
| start date | fecha de inicio | Form errors: name of a field, inside a message |
| title | título | Form errors: name of a field, inside a message |
| unit type | tipo de vivienda | Form errors: name of a field, inside a message |
| unit type name | nombre del tipo de vivienda | Form errors: name of a field, inside a message |
| :name is now in the :family family. | :name ya está en la familia :family. | Message (added to family) |
| It is back. | Ya está de vuelta. | Message after bringing something back (restored) |
| You are not allowed to do that. | No tenés permiso para hacer eso. | Message anywhere (not allowed) |
| The page was open for a long time. Try again. | La página estuvo abierta mucho tiempo. Probá de nuevo. | Message anywhere, after a page sat open too long (page expired) |
| Too many tries in a row. Wait a moment and try again. | Demasiados intentos seguidos. Esperá un momento y probá de nuevo. | Message anywhere, after too many tries (too many attempts) |
| This changed in the meantime. Check it and try again. | Esto cambió mientras tanto. Miralo y probá de nuevo. | Message anywhere, when something changed meanwhile (changed meanwhile) |
| &laquo; Previous | &laquo; Anterior | Pager of long lists |
| Next &raquo; | Siguiente &raquo; | Pager of long lists |
| Next page | Página siguiente | Pager of long lists: read by screen readers |
| Pages | Páginas | Pager of long lists: read by screen readers |
| Previous page | Página anterior | Pager of long lists: read by screen readers |
| {from}–{to} of {total} | {from}–{to} de {total} | Pager of long lists: text |
| Search | Buscar | Search boxes: read by screen readers; Platform console: UI Lab samples: title |
| Administration | Administración | menu group, admins |
| Details | Detalles | not shown today, kept because the code still names it |
| Overview | Resumen | not shown today, kept because the code still names it |
| Pending invitations | Invitaciones pendientes | not shown today, kept because the code still names it |
| Unit types | Tipos de vivienda | not shown today, kept because the code still names it |
| Run it | Hacelo | step title |
| Unassigned | Sin asignar | unit with no family yet |
| Member guide | Guía para cooperativistas | Member guide: title |
| {app} is where your cooperative organizes the assignment of its homes. Here you follow the project, share with the other families, and rank the units your family would like. Look for the <strong>?</strong> icons in the app: they explain each part, and many offer <em>Tell me more</em>, a short guided tour. | {app} es donde tu cooperativa organiza la adjudicación de sus viviendas. Acá seguís el proyecto, compartís con las demás familias y ordenás las viviendas que tu familia prefiere. Buscá los íconos <strong>?</strong> en la aplicación: explican cada parte, y muchos ofrecen <em>Contame más</em>, un breve recorrido guiado. | Member guide: text |
| Your project's home | El inicio de tu proyecto | Member guide: heading |
| The first page shows the project at a glance: its families, people and units (each one leads to its page <strong>(1)</strong>), what was shared lately, what is coming up, and the lottery. | La primera página muestra el proyecto de un vistazo: sus familias, cooperativistas y viviendas (cada número lleva a su página <strong>(1)</strong>), lo último que se compartió, lo que viene y el sorteo. | Member guide: text |
| The project's home page | El inicio del proyecto | Member guide: image text |
| The project at a glance. | El proyecto de un vistazo. | Member guide: picture caption |
| Joining | Cómo sumarte | Member guide: heading |
| You join a project from the invitation you receive by email. If you have no account, you choose a password. If you already have one, you sign in and accept. One account works for all your projects. | Te sumás a un proyecto con la invitación que te llega por correo. Si no tenés cuenta, elegís una contraseña. Si ya tenés, entrás y aceptás. Con una sola cuenta participás en todos tus proyectos. | Member guide: text |
| You can also find a project yourself: <em>Find a project</em>, in the project menu, lists those open to it. Some let you in at once. In others the admins decide. Once in, you join your family: look for yours in <em>Join your family</em> and ask to join it, or create it if it is not there. | También podés buscar un proyecto por tu cuenta: <em>Encontrar un proyecto</em>, en el menú de proyectos, muestra los que se dejan encontrar. Algunos te dejan entrar en el momento. En otros deciden los administradores. Una vez adentro, te sumás a tu familia: buscala en <em>Sumarte a tu familia</em> y pedí sumarte, o creala si no está. | Member guide: text |
| Your family | Tu familia | Member guide: heading |
| In the lottery your family takes part as one: its members share one ranking, and it receives one unit. In <em>People</em> you see the cooperative by family or everyone <strong>(1)</strong>. | En el sorteo, tu familia participa como una sola: comparte un orden de preferencias y recibe una vivienda. En <em>Cooperativistas</em> ves la cooperativa por familia o a todos juntos <strong>(1)</strong>. | Member guide: text |
| The families of the project | Las familias del proyecto | Member guide: image text |
| The cooperative, by family or everyone. | La cooperativa, por familia o todos juntos. | Member guide: picture caption |
| Open your family's card. | Abrí la tarjeta de tu familia. | Member guide: text |
| Press <em>Invite a family member</em> <strong>(1)</strong>: your family is already chosen. | Tocá <em>Invitar a un familiar</em> <strong>(1)</strong>: tu familia ya viene elegida. | Member guide: text |
| Write their email and send. They receive the invitation. | Escribí su correo y mandala: le llega la invitación. | Member guide: text |
| A family's details | Los datos de una familia | Member guide: image text |
| Inviting someone into your family. | Invitar a alguien a tu familia. | Member guide: picture caption |
| Joining a family, or changing it | Sumarte a una familia, o cambiarla | Member guide: heading |
| Not in your family yet? <em>Join your family</em>, in the menu, lists the project's families. Ask to join yours, and anyone in it can accept you. If it is not there, create it. If a family declines you, you cannot ask again unless someone in it lifts that. | ¿Todavía no estás en tu familia? <em>Sumarte a tu familia</em>, en el menú, muestra las familias del proyecto. Pedí sumarte a la tuya, y cualquiera de ella puede aceptarte. Si no está, creala. Si una familia te rechaza, no podés volver a pedirlo, salvo que alguien de ella levante el rechazo. | Member guide: text |
| In the wrong family? Open your family's card and press <em>Wrong family?</em>, at the bottom, to ask to join the right one or create it. You stay where you are until it is settled. Where the project takes guests, you can also just leave your family. A family with nobody left goes away, until an admin invites someone into it. | ¿En la familia equivocada? Abrí la tarjeta de tu familia y tocá <em>¿Familia equivocada?</em>, abajo de todo, para pedir sumarte a la correcta o crearla. Seguís donde estás hasta que se resuelva. Si el proyecto permite invitados, también podés simplemente salir de tu familia. Una familia sin nadie desaparece, hasta que un administrador invite a alguien a ella. | Member guide: text |
| Ranking the units | Ordenar las viviendas | Member guide: heading |
| In <em>Lottery</em> you rank the units of your family's type, from the most to the least wanted. Every change is saved at once, for the whole family, until the lottery runs. Only your family sees its ranking. | En <em>Sorteo</em> ordenás las viviendas del tipo de tu familia, de la que más querés a la que menos. Cada cambio se guarda al momento, para toda la familia, hasta que se haga el sorteo. Solo tu familia ve su orden. | Member guide: text |
| Move a unit in the list <strong>(1)</strong>: drag it, use its arrows, or pick it up with Space and move it with the arrow keys. | Mové una vivienda en la lista <strong>(1)</strong>: arrastrala, usá sus flechas, o tomala con la barra espaciadora y movela con las flechas del teclado. | Member guide: text |
| Or use the plan: drag a unit from the plan onto a numbered place under it. A unit you click on the plan lights up in your list. | O usá el plano: arrastrá una vivienda del plano a uno de los lugares numerados que hay debajo. La vivienda que tocás en el plano se ilumina en tu lista. | Member guide: text |
| Your family's ranking | El orden de tu familia | Member guide: image text |
| Your preferences, most wanted first. | Tus preferencias, la que más querés primero. | Member guide: picture caption |
| The plan with the places of the ranking | El plano con los lugares del orden | Member guide: image text |
| Ranking from the plan: drop a unit on its place <strong>(1)</strong>. | Ordenar desde el plano: soltá la vivienda en su lugar <strong>(1)</strong>. | Member guide: picture caption |
| The plan zooms like a map: hold Ctrl (⌘ on a Mac) and scroll, pinch on a phone, or use its buttons. Its corner button shows it over the whole window. | El plano se acerca como un mapa: mantené Ctrl (⌘ en una Mac) y usá la rueda, pellizcá en el celular, o usá sus botones. El botón de la esquina lo muestra en toda la pantalla. | Member guide: text |
| The lottery | El sorteo | Member guide: heading |
| On its date, an admin runs the lottery, and you see it work step by step. The lottery first gives the family that would be worst off the best it can, then makes the whole project as happy as possible. Only ties are settled at random. The result is final, and you see which unit each family received. If an admin ever invalidates a lottery, everyone sees who did it, when and why. | En su fecha, un administrador hace el sorteo, y lo ves avanzar paso a paso. El sorteo primero cuida a la familia que quedaría peor, y le da lo mejor posible. Después busca la mayor conformidad de todo el proyecto. Solo los empates se resuelven al azar. El resultado es definitivo, y ves qué vivienda recibió cada familia. Si alguna vez un administrador invalida un sorteo, todos ven quién lo hizo, cuándo y por qué. | Member guide: text |
| Life of the project | La vida del proyecto | Member guide: heading |
| <strong>Events</strong>: meetings, assemblies and work days. When an event asks, say whether you will go <strong>(1)</strong>. | <strong>Eventos</strong>: reuniones, asambleas y jornadas de trabajo. Cuando un evento lo pregunta, avisá si vas a ir <strong>(1)</strong>. | Member guide: text |
| The project's events | Los eventos del proyecto | Member guide: image text |
| What is coming up. | Lo que viene. | Member guide: picture caption |
| <strong>Gallery and files</strong>: photos, videos, documents and audio, for the people of the project only. Share yours <strong>(1)</strong>. Open a photo and move to the next with the arrows, the keyboard or a swipe. Recordings are with the documents, under <em>Files</em>, and play right there, faster if you like. | <strong>Galería y archivos</strong>: fotos, videos, documentos y audio, solo para la gente del proyecto. Compartí lo tuyo <strong>(1)</strong>. Abrí una foto y pasá a la siguiente con las flechas, el teclado o deslizando. Las grabaciones están con los documentos, en <em>Archivos</em>, y se escuchan ahí mismo, más rápido si querés. | Member guide: text |
| The project's files | Los archivos del proyecto | Member guide: image text |
| Everything the cooperative shares. | Todo lo que comparte la cooperativa. | Member guide: picture caption |
| <strong>Notifications</strong>: the bell tells you what happened, as it happens. | <strong>Notificaciones</strong>: la campana te cuenta lo que pasa, en el momento. | Member guide: text |
| The Assistant | El Asistente | Member guide: heading |
| In your messages, the Assistant is always at hand. Ask it how anything in the app works: it explains and takes you there. It is an automatic helper, for questions about the app. | En tus mensajes, el Asistente siempre está a mano. Preguntale cómo funciona cualquier cosa de la aplicación: te lo explica y te lleva hasta ahí. Es una ayuda automática, para preguntas sobre la aplicación. | Member guide: text |
| Your account | Tu cuenta | Member guide: heading |
| The menu of your picture has your profile, your password and your preferences: the language (on every device, and in your emails) and the appearance (light, dark, colours, high contrast). If you change your email, we send a confirmation to the new address. It changes once you confirm it. | En el menú de tu foto están tu perfil, tu contraseña y tus preferencias: el idioma (en todos tus dispositivos, y en tus correos) y la apariencia (claro, oscuro, colores, alto contraste). Si cambiás tu correo, te mandamos una confirmación a la dirección nueva. Cambia cuando la confirmás. | Member guide: text |
| Your preferences | Tus preferencias | Member guide: image text |
| The language and the look of the app. | El idioma y el aspecto de la aplicación. | Member guide: picture caption |
| Admin guide | Guía para administradores | Admin guide: title |
| Admins set up a project and run its lottery. Their tools are in the <em>Administration</em> group of the menu: <em>Setup</em> lists, in order, what is left. Every <strong>?</strong> explains its page, and offers a guided tour. | Los administradores ponen a punto el proyecto y hacen su sorteo. Sus herramientas están en el grupo <em>Administración</em> del menú: <em>Puesta a punto</em> muestra, en orden, lo que falta. Cada <strong>?</strong> explica su página y ofrece un recorrido guiado. | Admin guide: text |
| Starting a project | Crear un proyecto | Admin guide: heading |
| Anyone with an account can start a project, and becomes its first admin. A new project is limited, with room for most cooperatives (people, families and units, invitations a day, file storage). The {app} team can make it unlimited: ask from <em>Setup</em>, under <em>Limited</em>, whenever you like. Until the lottery has run, the project's home and <em>Setup</em> list what is left to do, in order <strong>(1)</strong>. | Cualquiera con una cuenta puede crear un proyecto, y pasa a ser su primer administrador. Un proyecto nuevo es limitado, con lugar para la mayoría de las cooperativas (personas, familias y viviendas, invitaciones por día, espacio para archivos). El equipo de {app} puede hacerlo ilimitado: pedilo desde <em>Puesta a punto</em>, en <em>Limitado</em>, cuando quieras. Hasta que se hace el sorteo, el inicio del proyecto y <em>Puesta a punto</em> muestran, en orden, lo que falta <strong>(1)</strong>. | Admin guide: text |
| What is left to set up | Lo que falta | Admin guide: image text |
| The path to the lottery, step by step. | El camino al sorteo, paso a paso. | Admin guide: picture caption |
| Setting it up | Ponerlo a punto | Admin guide: heading |
| <strong>Unit types</strong>: groups of equivalent units, like "2 bedrooms". Each type has its own lottery. | <strong>Tipos de vivienda</strong>: grupos de viviendas equivalentes, como «2 dormitorios». Cada tipo tiene su propio sorteo. | Admin guide: text |
| <strong>Units</strong>: each with its type. <em>Add and continue with another</em> lets you add many quickly, and you can create a new type on the way. <em>Units</em> lists them by type <strong>(1)</strong>. | <strong>Viviendas</strong>: cada una con su tipo. <em>Agregar y seguir con otra</em> te deja agregar muchas rápido, y podés crear un tipo nuevo en el camino. <em>Viviendas</em> las muestra por tipo <strong>(1)</strong>. | Admin guide: text |
| <strong>Families</strong>: each with its type, and someone invited into it. There should be as many units as families of each type. | <strong>Familias</strong>: cada una con su tipo, y alguien invitado a ella. Debería haber tantas viviendas como familias de cada tipo. | Admin guide: text |
| <strong>Invitations</strong>: invite people into their family (or as fellow admins). Families can then invite their own people. | <strong>Invitaciones</strong>: invitá a la gente a su familia (o como administradores). Después, las familias pueden invitar a su propia gente. | Admin guide: text |
| <strong>Plan</strong>: place the units and draw what surrounds them, on as many floors as needed. | <strong>Plano</strong>: ubicá las viviendas y dibujá lo que tienen alrededor, en todos los pisos que hagan falta. | Admin guide: text |
| The units by type | Las viviendas por tipo | Admin guide: image text |
| The units, by type. | Las viviendas, por tipo. | Admin guide: picture caption |
| Drawing the plan | Dibujar el plano | Admin guide: heading |
| On <em>Units</em>, press <em>Edit the plan</em> (on a computer: drawing takes a large screen and a mouse). One admin draws at a time. Others are told who is drawing. | En <em>Viviendas</em>, tocá <em>Editar el plano</em> (en una computadora: para dibujar hace falta una pantalla grande y un mouse). Dibuja un administrador a la vez. A los demás les avisamos quién está dibujando. | Admin guide: text |
| Add what you need from the palette <strong>(1)</strong>: units (their details first), streets, green areas, common spaces, parking, playgrounds, entrances. | Agregá lo que necesites desde la paleta <strong>(1)</strong>: viviendas (primero sus datos), calles, áreas verdes, espacios comunes, estacionamientos, juegos, entradas. | Admin guide: text |
| Drag a shape to move it, its corners to resize it, an edge to move that side. It lines up with what is around it, with a dashed guide. | Arrastrá una forma para moverla, sus esquinas para cambiarle el tamaño y un borde para mover ese lado. Se alinea con lo que tiene alrededor, con una guía punteada. | Admin guide: text |
| Right-click a shape for all you can do with it: its name, rotating it, its colour, a floor up or down, bringing it to the front, copying or removing it. Double-click it to name it. | Con clic derecho tenés todo lo de una forma: su nombre, girarla, su color, un piso arriba o abajo, traerla al frente, copiarla o quitarla. Doble clic para ponerle nombre. | Admin guide: text |
| It saves by itself <strong>(2)</strong>. <em>Done editing</em> closes it. After a long while with no changes, it asks whether you are still drawing, then closes, saved, so others can draw. | Se guarda solo <strong>(2)</strong>. <em>Terminar de editar</em> lo cierra. Si pasa mucho rato sin cambios, pregunta si seguís dibujando y después se cierra, guardado, para que otros puedan dibujar. | Admin guide: text |
| The plan editor | El editor del plano | Admin guide: image text |
| The palette <strong>(1)</strong> and the saving state <strong>(2)</strong>. | La paleta <strong>(1)</strong> y el estado del guardado <strong>(2)</strong>. | Admin guide: picture caption |
| A shape's menu | El menú de una forma | Admin guide: image text |
| A shape's menu: all you can do with it. | El menú de una forma: todo lo que se puede hacer con ella. | Admin guide: picture caption |
| Who can join, guests and features | Quién se suma, invitados y funciones | Admin guide: heading |
| In <em>Settings</em>, <em>Project</em>, choose whether the project shows in <em>Find a project</em>, who can join, whether it allows guests (people who have not joined their family: they take part in everything but the lottery, and do not see contact details), and which features it has: events, gallery, files, forum and chat. A feature turned off is hidden and keeps what it holds. Without guests, whoever enters joins their family first. | En <em>Configuración</em>, <em>Proyecto</em>, elegí si el proyecto aparece en <em>Encontrar un proyecto</em>, quién puede sumarse, si permite invitados (personas que todavía no se sumaron a su familia: participan en todo menos en el sorteo y no ven datos de contacto) y qué funciones tiene: eventos, galería, archivos, foro y chat. Una función apagada se oculta y conserva lo que tiene. Sin invitados, quien entra primero se suma a su familia. | Admin guide: text |
| Invitations | Invitaciones | Admin guide: heading |
| In <em>Invitations</em>, invite each person by email into their family, or as a fellow admin <strong>(1)</strong>. The pending ones stay listed until accepted. | En <em>Invitaciones</em>, invitá a cada persona por correo a su familia, o como administrador <strong>(1)</strong>. Las pendientes quedan a la vista hasta que se aceptan. | Admin guide: text |
| Someone already in the project joins the family at once, and is told by email. If you show the project in <em>Find a project</em> (<em>Project</em> tab), those asking to join appear here too, for you to accept or decline. | Quien ya está en el proyecto se suma a la familia en el momento, y le avisamos por correo. Si mostrás el proyecto en <em>Encontrar un proyecto</em> (pestaña <em>Proyecto</em>), quienes piden sumarse también aparecen acá, para que los aceptes o rechaces. | Admin guide: text |
| Invitations | Invitaciones | Admin guide: image text |
| Inviting people into the project. | Invitar gente al proyecto. | Admin guide: picture caption |
| People and roles | Personas y roles | Admin guide: heading |
| In <em>People</em>, viewing <em>Everyone</em>, you move someone to another family, make them an admin, or remove them from the project. An admin can also be in a family. A project always keeps at least one admin. | En <em>Cooperativistas</em>, en la vista <em>Todos</em>, podés pasar a alguien a otra familia, hacerlo administrador o quitarlo del proyecto. Un administrador también puede estar en una familia. Un proyecto siempre conserva al menos un administrador. | Admin guide: text |
| Events | Eventos | Admin guide: heading |
| Drafts are visible to admins only, until published. An event can ask people whether they will go, and you see who goes. | Los borradores solo los ven los administradores, hasta que se publican. Un evento puede preguntar si la gente va a ir, y ves quiénes van. | Admin guide: text |
| The lottery | El sorteo | Admin guide: heading |
| Set its date in <em>Lottery</em>: families can change their rankings until then. Once the date arrives, check that each type has as many units as families, and run it. If the numbers differ you are asked to confirm, and some families or units are left over. The result is final and recorded step by step. | Fijá su fecha en <em>Sorteo</em>: las familias pueden cambiar su orden hasta entonces. Cuando llega la fecha, revisá que cada tipo tenga tantas viviendas como familias, y hacelo. Si los números no coinciden te pedimos que confirmes, y sobran familias o viviendas. El resultado es definitivo y queda registrado paso a paso. | Admin guide: text |
| Running the lottery | Hacer el sorteo | Admin guide: image text |
| Its date <strong>(1)</strong>, and running it <strong>(2)</strong>. | Su fecha <strong>(1)</strong>, y hacerlo <strong>(2)</strong>. | Admin guide: picture caption |
| Only in exceptional cases (a family registered with the wrong type, a missing family…), invalidate the lottery: every assignment is cleared, and everyone in the project reads who did it and why. The lottery can then run again, and the earlier one stays on record. | Solo en casos excepcionales (una familia registrada con el tipo equivocado, una familia que faltaba…), invalidá el sorteo: se borran todas las asignaciones, y todos en el proyecto leen quién lo hizo y por qué. Después se puede volver a hacer, y el anterior queda registrado. | Admin guide: text |
| Deleting the project | Borrar el proyecto | Admin guide: heading |
| In <em>Settings</em>, by typing its name. A project with a history (other people, events, files, a lottery) is archived for the platform's records: it is gone for everyone, and only a superadmin can bring it back. | En <em>Configuración</em>, escribiendo su nombre. Un proyecto con historia (otras personas, eventos, archivos, un sorteo) se archiva para el registro de la plataforma: desaparece para todos, y solo un superadministrador puede recuperarlo. | Admin guide: text |
| Superadmin guide | Guía para superadministradores | Superadmin guide: title |
| Superadmins look after the platform from the <em>Platform console</em> (in the menu of your picture). You can also enter any project and do anything its admins can: see the admin guide. | Los superadministradores cuidan la plataforma desde la <em>Consola de la plataforma</em> (en el menú de tu foto). También podés entrar a cualquier proyecto y hacer todo lo que hacen sus administradores: mirá la guía para administradores. | Superadmin guide: text |
| Approving projects | Aprobar proyectos | Superadmin guide: heading |
| Anyone can start a project. Until approved, it has the limits set for the platform (people, families and units, invitations a day, file storage, lottery size), which keeps abuse in check. <em>Projects</em> lists those asking for approval first. Approve a project when you trust its admins: its limits are lifted and its people are told. Projects you start yourself are approved at once. | Cualquiera puede crear un proyecto. Hasta que se aprueba, tiene los límites fijados para la plataforma (personas, familias y viviendas, invitaciones por día, espacio para archivos, tamaño del sorteo), lo que evita abusos. <em>Proyectos</em> muestra primero los que piden aprobación. Aprobá un proyecto cuando confíes en sus administradores: se le quitan los límites y se avisa a su gente. Los proyectos que creás vos quedan aprobados enseguida. | Superadmin guide: text |
| The platform's projects | Los proyectos de la plataforma | Superadmin guide: image text |
| Projects asking for approval come first <strong>(1)</strong>. | Primero, los que piden aprobación <strong>(1)</strong>. | Superadmin guide: picture caption |
| Accounts | Cuentas | Superadmin guide: heading |
| <em>Accounts</em> lists everyone with an account. The superadmin role is given from a page of its own, <em>Superadmins</em>, by exact email: a rare role, given with care. | <em>Cuentas</em> muestra a todas las personas con cuenta. El rol de superadministrador se da desde su propia página, <em>Superadministradores</em>, con el correo exacto: un rol poco común, que se da con cuidado. | Superadmin guide: text |
| Archived projects | Proyectos archivados | Superadmin guide: heading |
| When admins delete a project with a history, it is archived: <em>Projects</em> shows it under <em>Archived</em>. Restore it from there, or delete it for good after a summary of what goes with it. | Cuando los administradores borran un proyecto con historia, se archiva: <em>Proyectos</em> lo muestra en <em>Archivado</em>. Desde ahí lo restaurás, o lo borrás para siempre después de ver un resumen de todo lo que se va con él. | Superadmin guide: text |
| Settings | Configuración | Superadmin guide: heading |
| <em>Settings</em> holds what applies to every project. The lottery is done in an instant, which feels like nothing happened. So you can set how long it takes as people see it <strong>(1)</strong>, step by step. The result is the same. | <em>Configuración</em> reúne lo que vale para todos los proyectos. El sorteo se resuelve en un instante, y parece que no pasó nada. Por eso podés fijar cuánto dura a la vista de la gente <strong>(1)</strong>, paso a paso. El resultado es el mismo. | Superadmin guide: text |
| The platform's settings | La configuración de la plataforma | Superadmin guide: image text |
| How long a lottery takes. | Cuánto dura un sorteo. | Superadmin guide: picture caption |
| A new deployment | Una instalación nueva | Superadmin guide: heading |
| A deployment with no account at all opens only once its owner, its first superadmin, is set in its settings (<code>OWNER_EMAIL</code>, <code>OWNER_PASSWORD</code>). Until then it shows only that it is under construction. | Una instalación sin ninguna cuenta abre recién cuando su dueño, su primer superadministrador, figura en su configuración (<code>OWNER_EMAIL</code>, <code>OWNER_PASSWORD</code>). Hasta entonces solo muestra que está en construcción. | Superadmin guide: text |
| Notifications | Notificaciones | Superadmin guide: heading |
| You are told when a project is started, and when one asks for approval. | Te avisamos cuando se crea un proyecto, y cuando alguno pide aprobación. | Superadmin guide: text |
| Frequently asked questions | Preguntas frecuentes | Questions page, members: title |
| Quick answers for the people of a project | Respuestas rápidas para la gente del proyecto | Questions page, members: subtitle |
| Read the full guide | Leer la guía completa | Questions page, members: link to the guide |
| How do I join a project? | ¿Cómo me sumo a un proyecto? | Questions page, members: question |
| With the invitation you receive by email, from an admin or from someone in your family. Without an account you choose a password. With one, you sign in and accept. Pending invitations also appear in Your projects. You can also find one under Find a project, if its admins list it. | Con la invitación que te llega por correo, de un administrador o de alguien de tu familia. Si no tenés cuenta, elegís una contraseña. Si la tenés, entrás y aceptás. Las invitaciones pendientes también aparecen en Tus proyectos. También podés buscar uno en Encontrar un proyecto, si sus administradores lo muestran. | Questions page, members: answer |
| Who sees my family's ranking? | ¿Quién ve el orden de mi familia? | Questions page, members: question |
| Only your family. Other families never see it, before or after the lottery. | Solo tu familia. Las demás familias nunca lo ven, ni antes ni después del sorteo. | Questions page, members: answer |
| Do I have to rank every unit? | ¿Tengo que ordenar todas las viviendas? | Questions page, members: question |
| No. Move the ones you care about. The rest keep a place of their own at the end of your ranking. | No. Mové las que te importan. Las demás conservan un lugar propio al final de tu orden. | Questions page, members: answer |
| Can two of us change the ranking? | ¿Podemos cambiar el orden entre dos? | Questions page, members: question |
| Yes. It is one ranking for the whole family, and any member can change it until the lottery runs. The latest change is the one that counts. | Sí. Es un solo orden para toda la familia, y cualquier integrante puede cambiarlo hasta que se haga el sorteo. Vale el último cambio. | Questions page, members: answer |
| How does the lottery decide? | ¿Cómo decide el sorteo? | Questions page, members: question |
| It first gives the family that would be worst off the best it can, then makes the whole project as happy as possible. Only ties are settled at random. | Primero cuida a la familia que quedaría peor, y le da lo mejor posible. Después busca la mayor conformidad de todo el proyecto. Solo los empates se resuelven al azar. | Questions page, members: answer |
| Can the result change? | ¿Puede cambiar el resultado? | Questions page, members: question |
| It is final. Only in exceptional cases an admin may invalidate a lottery. Everyone then sees who did it, when and why, and the lottery runs again. | Es definitivo. Solo en casos excepcionales un administrador puede invalidar un sorteo. Entonces todos ven quién lo hizo, cuándo y por qué, y se vuelve a hacer. | Questions page, members: answer |
| Who sees the files I share? | ¿Quién ve los archivos que comparto? | Questions page, members: question |
| Everyone in the project, and nobody outside it. You can edit the description or remove what you shared. | Todos en el proyecto, y nadie de afuera. Podés editar la descripción o quitar lo que compartiste. | Questions page, members: answer |
| I changed my email and nothing happened. | Cambié mi correo y no pasó nada. | Questions page, members: question |
| We sent a confirmation link to the new address. Your account keeps the old one until you open it. | Te mandamos un enlace de confirmación a la dirección nueva. Tu cuenta sigue con la anterior hasta que lo abras. | Questions page, members: answer |
| Frequently asked questions | Preguntas frecuentes | Questions page, admins: title |
| Quick answers for admins | Respuestas rápidas para administradores | Questions page, admins: subtitle |
| Read the admin guide | Leer la guía para administradores | Questions page, admins: link to the guide |
| Why are there limits in my project? | ¿Por qué mi proyecto tiene límites? | Questions page, admins: question |
| New projects have limits until a superadmin approves them, to prevent abuse. Ask for approval from Setup. | Los proyectos nuevos tienen límites hasta que un superadministrador los aprueba, para evitar abusos. Pedí la aprobación desde Puesta a punto. | Questions page, admins: answer |
| What is a unit type? | ¿Qué es un tipo de vivienda? | Questions page, admins: question |
| A group of equivalent units, like 2 bedrooms. Each family has one type, and receives a unit of that type. | Un grupo de viviendas equivalentes, como 2 dormitorios. Cada familia tiene un tipo, y recibe una vivienda de ese tipo. | Questions page, admins: answer |
| What if there are more units than families? | ¿Y si hay más viviendas que familias? | Questions page, admins: question |
| You can still run the lottery. You are asked to confirm, and some units are left without a family (or families without a unit, the other way round). | Igual podés hacer el sorteo. Te pedimos que confirmes, y algunas viviendas quedan sin familia (o familias sin vivienda, al revés). | Questions page, admins: answer |
| How do I add many units? | ¿Cómo agrego muchas viviendas? | Questions page, admins: question |
| Use Add and continue with another: the form stays ready for the next unit, with the same type. | Usá Agregar y seguir con otra: el formulario queda listo para la próxima, con el mismo tipo. | Questions page, admins: answer |
| Can an admin also be in a family? | ¿Un administrador puede estar en una familia? | Questions page, admins: question |
| Yes. Invite them as an admin and into a family, or change their role and family in People. | Sí. Invitalo como administrador y a una familia, o cambiá su rol y su familia en Cooperativistas. | Questions page, admins: answer |
| Someone lost their invitation. | Alguien perdió su invitación. | Questions page, admins: question |
| Withdraw it in Invitations and send a new one. The old link stops working. | Retirala en Invitaciones y mandá una nueva. El enlace anterior deja de funcionar. | Questions page, admins: answer |
| Can I undo the lottery? | ¿Puedo deshacer el sorteo? | Questions page, admins: question |
| Only by invalidating it, giving a reason that everyone in the project reads. The lottery stays on record, and the lottery can run again. | Solo invalidándolo, con un motivo que todos en el proyecto leen. El sorteo queda registrado, y se puede volver a hacer. | Questions page, admins: answer |
| Who sees drafts? | ¿Quién ve los borradores? | Questions page, admins: question |
| Only admins, until you publish them. | Solo los administradores, hasta que los publicás. | Questions page, admins: answer |
| Approve {project}? | ¿Aprobar {project}? | Platform console: Projects: confirmation title |
| Delete {project} for good? | ¿Borrar {project} para siempre? | Platform console: Projects: confirmation title |
| Restore {project}? | ¿Restaurar {project}? | Platform console: Projects: confirmation title |
| Approve | Aprobar | Platform console: Projects: dialog text |
| Approving | Aprobar | Platform console: Projects: guided tour step title, Projects |
| Approve a project when you trust its admins: its limits are lifted and its people are told. | Aprobá un proyecto cuando confíes en sus administradores: se le quitan los límites y se avisa a su gente. | Platform console: Projects: guided tour, step text |
| Projects anyone starts have limits until approved. Those asking for approval come first. | Los proyectos que crea cualquiera tienen límites hasta que se aprueban. Primero aparecen los que piden aprobación. | Platform console: Projects: guided tour, step text |
| Projects asking for approval | Proyectos que piden aprobación | Platform console: Projects: guided tour, step title |
| Every project on the platform. | Todos los proyectos de la plataforma. | Platform console: Projects: hint |
| All | Todos | Platform console: Projects: label |
| Approved | Aprobado | Platform console: Projects: label |
| Awaiting approval | Esperando aprobación | Platform console: Projects: label |
| Lottery runs | Sorteos hechos | Platform console: Projects: label |
| Not approved | Sin aprobar | Platform console: Projects: label |
| Active for {time}: started on {start}, archived on {end}{by}. | Estuvo activo {time}: empezó el {start} y se archivó el {end}{by}. | Platform console: Projects: text |
| Anyone can start a project. It has limits until a superadmin approves it, to prevent abuse. | Cualquiera puede crear un proyecto. Tiene límites hasta que un superadministrador lo aprueba, para evitar abusos. | Platform console: Projects: text |
| Asks for approval | Pide aprobación | Platform console: Projects: text |
| Delete for good | Borrar para siempre | Platform console: Projects: text |
| Everything in it is deleted, its files too. This cannot be undone. | Se borra todo lo que tiene, también sus archivos. No se puede deshacer. | Platform console: Projects: text |
| No projects match. | Ningún proyecto coincide. | Platform console: Projects: text |
| {count} day | {count} día | Platform console: Projects: text |
| {count} days | {count} días | Platform console: Projects: text |
| {count} month | {count} mes | Platform console: Projects: text |
| {count} months | {count} meses | Platform console: Projects: text |
| About approvals | Sobre las aprobaciones | Platform console: Projects: title |
| It comes back as it was, for everyone in it. | Vuelve como estaba, para todos los que participan. | Platform console: Projects: title |
| You trust its admins: the project's limits are lifted. | Confiás en sus administradores: se le quitan los límites al proyecto. | Platform console: Projects: title |
| Accounts | Cuentas | Platform console menu: label; Platform console: Accounts: title |
| Activity | Actividad | Platform console menu: label; Platform console: Activity: title |
| Deployments | Despliegues | Platform console menu: label; Platform console: Deployments: title |
| Errors | Errores | Platform console menu: label; Platform console: Errors: title |
| Projects | Proyectos | Platform console menu: label; Platform console: Projects: title |
| Superadmins | Superadministradores | Platform console menu: label; Platform console: Superadmins: title |
| UI Lab | Laboratorio de interfaz | Platform console menu: label; Platform console: UI Lab: title |
| :name is blocked: out of every project, and unable to sign in. | :name quedó bloqueado: fuera de todos los proyectos y sin poder entrar. | Message about an account (account blocked) |
| Your account was deleted. | Se borró tu cuenta. | Message about an account (account deleted) |
| :name can sign in again. | :name puede volver a entrar. | Message about an account (account unblocked) |
| Block | Bloquear | Platform console: Accounts: button |
| Block {name}? | ¿Bloquear a {name}? | Platform console: Accounts: confirmation title |
| Delete the account of {name}? | ¿Borrar la cuenta de {name}? | Platform console: Accounts: confirmation title |
| They leave every project at once and cannot sign in. Their email stays taken, so they cannot join again. | Sale de todos los proyectos al momento y no puede entrar. Su correo queda ocupado, así que no puede volver a sumarse. | Platform console: Accounts: dialog text |
| How many projects it takes part in, since when, and whether its email is confirmed. | En cuántos proyectos participa, desde cuándo y si su correo está confirmado. | Platform console: Accounts: guided tour, step text |
| Search by name or email. | Buscá por nombre o correo. | Platform console: Accounts: guided tour, step text |
| Each account | Cada cuenta | Platform console: Accounts: guided tour, step title |
| Find an account | Encontrar una cuenta | Platform console: Accounts: guided tour, step title |
| Everyone with an account on the platform. | Todas las personas con cuenta en la plataforma. | Platform console: Accounts: hint |
| Actions for {name} | Acciones para {name} | Platform console: Accounts: read by screen readers |
| Blocked | Bloqueado | Platform console: Accounts: text |
| Email not confirmed | Correo sin confirmar | Platform console: Accounts: text |
| Superadmin | Superadministrador | Platform console: Accounts: text |
| Unblock | Desbloquear | Platform console: Accounts: text |
| {count} project | {count} proyecto | Platform console: Accounts: text |
| {count} projects | {count} proyectos | Platform console: Accounts: text |
| Everyone with an account on the platform; blocking or deleting one is done here. | Todas las personas con cuenta en la plataforma. Acá se bloquea o se borra una cuenta. | Platform console: Accounts: title |
| It goes for good. Nothing stops them signing up again with the same email. | Se borra para siempre. Nada le impide registrarse otra vez con el mismo correo. | Platform console: Accounts: title |
| No account matches | Ninguna cuenta coincide | Platform console: Accounts: title |
| :name is now a superadmin. | :name ahora es superadministrador. | Message in Superadmins (superadmin granted) |
| :name is no longer a superadmin. | :name ya no es superadministrador. | Message in Superadmins (superadmin revoked) |
| Give the role | Dar el rol | Platform console: Superadmins: button |
| Give the superadmin role | Dar el rol de superadministrador | Platform console: Superadmins: button |
| Give the superadmin role? | ¿Dar el rol de superadministrador? | Platform console: Superadmins: confirmation title |
| Remove {name}'s superadmin role? | ¿Quitarle el rol de superadministrador a {name}? | Platform console: Superadmins: confirmation title |
| Superadmins see and manage every project on the platform, approve them and delete them for good. | Los superadministradores ven y gestionan todos los proyectos de la plataforma, los aprueban y los borran para siempre. | Platform console: Superadmins: dialog text |
| Each of them sees and manages every project. Remove the role once it is no longer needed. | Cada uno ve y gestiona todos los proyectos. Quitá el rol cuando ya no haga falta. | Platform console: Superadmins: guided tour, step text |
| To an existing account, by its email, exactly as they sign in. | A una cuenta que ya existe, con el correo con el que entra. | Platform console: Superadmins: guided tour, step text |
| Giving the role | Dar el rol | Platform console: Superadmins: guided tour, step title |
| Who has the role | Quién tiene el rol | Platform console: Superadmins: guided tour, step title |
| Exactly as they sign in | El mismo con el que entra | Platform console: Superadmins: hint beside the email field |
| Their account’s email | El correo de su cuenta | Platform console: Superadmins: label |
| Owner | Dueño | Platform console: Superadmins: read by screen readers |
| Give the role to someone | Dar el rol a alguien | Platform console: Superadmins: title |
| Remove the role | Quitar el rol | Platform console: Superadmins: title |
| Who manages the whole platform. A rare role, given with care. | Quienes gestionan toda la plataforma. Un rol poco común, que se da con cuidado. | Platform console: Superadmins: title |
| The platform settings were saved. | Se guardó la configuración de la plataforma. | Message in platform Settings (platform settings saved) |
| Everyone sees the lottery work, step by step, for at least this long. The result does not depend on it. | Todos ven el sorteo avanzar, paso a paso, durante al menos este tiempo. El resultado no depende de eso. | Platform console: Settings: guided tour, step text |
| How long a lottery takes | Cuánto dura un sorteo | Platform console: Settings: guided tour, step title |
| Everyone sees it work, step by step, for at least this long. The result is the same. | Todos lo ven avanzar, paso a paso, durante al menos este tiempo. El resultado es el mismo. | Platform console: Settings: hint |
| How the platform works for every project. | Cómo funciona la plataforma para cada proyecto. | Platform console: Settings: hint |
| How long a lottery takes, in seconds | Cuánto dura un sorteo, en segundos | Platform console: Settings: label |
| Platform settings | Configuración de la plataforma | Platform console: Settings: title |
| What applies to every project, like how long a lottery takes as people see it. | Lo que vale para todos los proyectos, como cuánto dura un sorteo a la vista de la gente. | Platform console: Settings: title |
| Nobody signed in | Sin entrar | Platform console: Activity: activity by someone not signed in |
| Search by person or name | Buscar por persona o nombre | Platform console: Activity: example inside a field |
| By person or name, by project, and by kind: changes, or sign-ins. | Por persona o nombre, por proyecto y por tipo: cambios o entradas. | Platform console: Activity: guided tour, step text |
| Each change with what it changed, from what to what; a person's own details only by name. Who did it, when, and from where. | Cada cambio con lo que cambió, de qué a qué; los datos de una persona, solo por su nombre. Quién lo hizo, cuándo y desde dónde. | Platform console: Activity: guided tour, step text |
| Narrow it down | Acotar | Platform console: Activity: guided tour, step title |
| Every change, and every way in, newest first. | Cada cambio y cada entrada, lo más nuevo primero. | Platform console: Activity: hint |
| Changes | Cambios | Platform console: Activity: label |
| Sign-ins | Entradas | Platform console: Activity: label |
| Every project | Todos los proyectos | Platform console: Activity: read by screen readers |
| Everything | Todo | Platform console: Activity: read by screen readers |
| Kind | Tipo | Platform console: Activity: read by screen readers |
| Account | Cuenta | Platform console: Activity: text |
| Changed | Cambiado | Platform console: Activity: text |
| Created | Creado | Platform console: Activity: text |
| Deleted | Borrado | Platform console: Activity: text |
| Event | Evento | Platform console: Activity: text |
| Failed sign-in | Entrada fallida | Platform console: Activity: text |
| File | Archivo | Platform console: Activity: text |
| Forum post | Mensaje del foro | Platform console: Activity: text |
| Forum topic | Tema del foro | Platform console: Activity: text |
| Invitation | Invitación | Platform console: Activity: text |
| Lottery run | Ejecución del sorteo | Platform console: Activity: text |
| Membership | Participación | Platform console: Activity: text |
| Password reset | Contraseña restablecida | Platform console: Activity: text |
| Platform setting | Configuración de la plataforma | Platform console: Activity: text |
| Request to join | Pedido para sumarse | Platform console: Activity: text |
| Signed in | Entró | Platform console: Activity: text |
| Signed out | Salió | Platform console: Activity: text |
| What changed, who changed it and when, on every project; and who signed in. Never what people read: only what they did. | Qué cambió, quién y cuándo, en cada proyecto; y quién entró. Nunca lo que la gente lee: solo lo que hizo. | Platform console: Activity: text |
| Nothing yet | Nada todavía | Platform console: Activity: title |
| Marked solved. | Marcado como resuelto. | Message in Errors (error solved) |
| What went wrong in the app, latest first. | Lo que falló en la aplicación, lo más reciente primero. | Platform console: Errors: hint |
| {count} time | {count} vez | Platform console: Errors: mark |
| {count} times | {count} veces | Platform console: Errors: mark |
| Mark {error} solved | Marcar {error} como resuelto | Platform console: Errors: read by screen readers |
| Each error once, with how often it happened. The owner is emailed when a new one appears. Mark it solved once fixed: it comes back if it happens again. | Cada error una vez, con cuántas veces ocurrió. Al dueño le llega un correo cuando aparece uno nuevo. Marcalo como resuelto una vez arreglado: vuelve si ocurre de nuevo. | Platform console: Errors: text |
| Hide the trace | Ocultar el rastro | Platform console: Errors: text |
| Last {last} · first {first} | Última: {last} · primera: {first} | Platform console: Errors: text |
| Show the trace | Mostrar el rastro | Platform console: Errors: text |
| Solved | Resuelto | Platform console: Errors: text |
| No errors | Sin errores | Platform console: Errors: title |
| Dev is being deployed from :branch. Follow it below. | Se está desplegando dev desde :branch. Seguilo abajo. | Message in Deployments, platform console (deploy dev) |
| Starting :environment over, with the demo data. Follow it below. | :environment empieza de cero, con los datos de demostración. Seguilo abajo. | Message in Deployments, platform console (deploy fresh) |
| Production is being deployed: it waits for approval on GitHub. | Se está desplegando producción: espera la aprobación en GitHub. | Message in Deployments, platform console (deploy production) |
| GitHub did not start it (:status). Check the deploy token. | GitHub no lo inició (:status). Revisá el token de despliegue. | Message in Deployments, platform console (deploy refused) |
| :environment is going back to the release before. | :environment vuelve a la versión anterior. | Message in Deployments, platform console (deploy rollback) |
| GitHub did not answer, so nothing started. Try again in a moment. | GitHub no respondió, así que no se inició nada. Probá de nuevo en un rato. | Message in Deployments, platform console (deploy unreachable) |
| Deploy dev | Desplegar dev | Platform console: Deployments: button |
| Start over | Empezar de cero | Platform console: Deployments: button |
| Deploy {branch} to dev? | ¿Desplegar {branch} en dev? | Platform console: Deployments: confirmation title |
| Put {environment} back to its release before? | ¿Volver {environment} a su versión anterior? | Platform console: Deployments: confirmation title |
| Start {environment} over? | ¿Empezar {environment} de cero? | Platform console: Deployments: confirmation title |
| Everything in dev, people and uploaded files included, is replaced by the demo data. | Todo lo que tiene dev, personas y archivos subidos incluidos, se reemplaza por los datos de demostración. | Platform console: Deployments: dialog text |
| Everything in it, people and uploaded files included, is replaced by the demo data. The release it runs stays. | Todo lo que tiene, personas y archivos subidos incluidos, se reemplaza por los datos de demostración. La versión que corre no cambia. | Platform console: Deployments: dialog text |
| Roll back | Volver atrás | Platform console: Deployments: dialog text |
| The one staging runs | La que corre staging | Platform console: Deployments: example inside a field |
| Deploy any branch to dev. Every deploy starts it over, with the demo data. | Desplegá cualquier rama en dev. Cada despliegue lo deja como nuevo, con los datos de demostración. | Platform console: Deployments: guided tour, step text |
| Each environment and the release it runs. Rolling back returns to the one before; starting over brings back the demo data. | Cada entorno y la versión que corre. Volver atrás regresa a la anterior; empezar de cero trae de vuelta los datos de demostración. | Platform console: Deployments: guided tour, step text |
| Every deployment and how it went, linked to GitHub. | Cada despliegue y cómo salió, con su enlace a GitHub. | Platform console: Deployments: guided tour, step text |
| Production gets what staging runs, once approved on GitHub. | Producción recibe lo que corre staging, una vez aprobado en GitHub. | Platform console: Deployments: guided tour, step text |
| Latest runs | Últimas ejecuciones | Platform console: Deployments: guided tour, step title |
| Releasing | Publicar | Platform console: Deployments: guided tour, step title |
| Trying a branch | Probar una rama | Platform console: Deployments: guided tour, step title |
| What runs where | Qué corre dónde | Platform console: Deployments: guided tour, step title |
| What each environment runs, and deploying it. | Qué corre cada entorno, y desplegarlo. | Platform console: Deployments: hint |
| Branch | Rama | Platform console: Deployments: label |
| Commit | Commit | Platform console: Deployments: label |
| Cancelled | Cancelado | Platform console: Deployments: text |
| Every deploy starts dev over, with the demo data. | Cada despliegue deja dev como nuevo, con los datos de demostración. | Platform console: Deployments: text |
| From main: the release staging runs, unless a commit of main is named. | Desde main: la versión que corre staging, salvo que se indique un commit de main. | Platform console: Deployments: text |
| GitHub did not answer. Try again in a moment. | GitHub no respondió. Probá de nuevo en un momento. | Platform console: Deployments: text |
| It waits for approval on GitHub. | Espera la aprobación en GitHub. | Platform console: Deployments: text |
| Live since {date} | En línea desde {date} | Platform console: Deployments: text |
| No runs yet. | Todavía no hay ejecuciones. | Platform console: Deployments: text |
| Nothing deployed yet. | Todavía no se desplegó nada. | Platform console: Deployments: text |
| Rolled back | Vuelto atrás | Platform console: Deployments: text |
| Up to date with main | Al día con main | Platform console: Deployments: text |
| Waiting for approval | Esperando aprobación | Platform console: Deployments: text |
| What each environment runs, deploying a branch to dev or main to production, going back to an earlier release, and starting dev or staging over with the demo data. | Qué corre en cada entorno: desplegar una rama en dev o main en producción, volver a una versión anterior y empezar dev o staging de cero con los datos de demostración. | Platform console: Deployments: text |
| {count} commit behind main | {count} commit detrás de main | Platform console: Deployments: text |
| {count} commits behind main | {count} commits detrás de main | Platform console: Deployments: text |
| Deploy production | Desplegar producción | Platform console: Deployments: title |
| The release before comes back at once; its database keeps the newer migrations. | La versión anterior vuelve al instante; su base de datos conserva las migraciones más nuevas. | Platform console: Deployments: title |
| Every component of the design system, in its states and variants. | Cada componente del sistema de diseño, con sus estados y variantes. | Platform console: UI Lab: hint |
| Sections | Secciones | Platform console: UI Lab: read by screen readers |
