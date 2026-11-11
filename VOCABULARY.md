# Vocabulary

The words MTAV uses for its domain, so that people and agents mean the same thing. Use these in
code, documents and conversation. In the interface, the word is the one in the last column, in the
person's language; only Uruguayan Spanish is shown here (`lang/` has the rest).

## People and their roles

| Word | What it means | In code | Spanish |
| --- | --- | --- | --- |
| **Person** | Anyone with an account. One account serves every project. | `User` | persona |
| **Superadmin** | Oversees the whole deployment: approves projects, and may do what an admin does in any project. A support account apart, invited on an address of its own: it takes part in no project. | `is_superadmin` | superadministrador |
| **Owner** | The deployment's first superadmin. A deployment with no owner stays closed, showing only that it is under construction. | `is_owner` | |
| **Admin** | Runs a project: sets it up, invites people, runs the lottery. May also be in a family. | `Membership.is_admin` | administrador |
| **Member** | Someone taking part in a project, in a family. "Member of a family" is the same person seen from the family. | `Membership` with `family_id` | cooperativista, integrante |
| **Guest** | Someone in a project that allows guests, who has not joined their family here. Takes part in everything but the lottery; cannot invite, rank or see contact details. | `Membership` with no family, `allows_guests` | invitado |
| **Newcomer** | Someone let into a project that allows no guests, who has not joined their family yet. Finding it is all they can do until then. | `needsFamilyIn` | |

A **membership** is the link between a person and a project, whatever their role.

**Say it this way:** people *join* a family; they may have a real family whether or not they joined it
here. Never say someone "has no family", "does not have a family", or "is familyless". Say they
"have not joined their family (yet)". Also avoid "user" where "person" or a role says more.

## Projects

| Word | What it means | In code | Spanish |
| --- | --- | --- | --- |
| **Deployment** | One installation of the app, with its own people and projects: dev, staging or production. | `MTAV_ENV` | |
| **Variant** | What a deployment is made to be: its name, landing page, words, and which parts of the app it offers. MTAV is one, running as dev, staging and production. Say "app variant" where "variant" alone is unclear. | | |
| **Module** | A part of the app a variant has or lacks. Every route belongs to one, except the few every variant has. | `module:…` | |
| **Project** | One housing cooperative building its homes. Everything else belongs to one. | `Project` | proyecto |
| **Limited** / **unlimited** | A project nobody vetted yet has limits that fit most cooperatives. A superadmin's approval makes it unlimited. A fact, never a warning. | `isApproved()` | limitado |
| **Directory** | Where listed projects can be found and joined. | `DirectoryService` | Encontrar un proyecto |
| **Listed** | Shown in the directory. | `listed` | |
| **Open** | Anyone can join at once; otherwise they ask, and its admins answer. | `open_to_join` | |
| **Feature** | A part of a project its admins can turn off: forum, chat, the Assistant, gallery, files, events. | `ProjectFeature` | |
| **Setup** | The road to the lottery, step by step, shown on a paper note. | `setupChecklist` | puesta a punto |
| **Archived** | Removed, but kept hidden for admins to see and bring back. | `Archivable` | archivado |

## Families

| Word | What it means | In code | Spanish |
| --- | --- | --- | --- |
| **Family** | The real household that takes part in the lottery as one: one ranking, one unit. It exists here while it holds someone: members, an open invitation, or the unit it received. | `Family` | familia |
| **Invitation** | An emailed link to a project, into a family or as an admin. Someone already in the project joins the family at once. | `Invitation` | invitación |
| **Join request** | Someone asking to enter a *project* from the directory; its admins answer. | `JoinRequest` | pedido |
| **Family join request** | Someone asking to enter a *family*; any of its members answers. | `FamilyJoinRequest` | pedido |
| **Decline** | A family's answer that the person is not in it. It stands until someone in that family lifts it, against admins too. | `declined_at` | rechazo |

## Homes and the lottery

| Word | What it means | In code | Spanish |
| --- | --- | --- | --- |
| **Unit** | One home. Never "house" or "apartment" in general terms. | `Unit` | vivienda |
| **Unit type** | Equivalent units, like "2 bedrooms". A family has one, and receives a unit of it. | `UnitType` | tipo de vivienda |
| **Plan** | The drawing of the site, with its buildings and units. | `Plan`, `PlanItem` | plano |
| **Preferences** | A family's units in the order it wants them. Its **ranking**; to set it is to **rank**. | `unit_preferences` | preferencias, orden |
| **Lottery** | How units reach families: it first makes the family that would be worst off as well off as possible, then the whole project as satisfied as possible. Only ties are broken at random. | `LotteryService` | sorteo |
| **Run** | One execution of the lottery, with its record. | `LotteryRun` | |
| **Invalidation** | An admin voiding a run in exceptional cases, with a reason everyone sees. | `invalidated` | |

Say **family preferences** where it could be confused: a person's own settings (text size, language,
light or dark) are also called preferences, in their profile.

**Say it this way:** the lottery is never a competition. Families do not compete, nobody wins or loses:
each family *receives* a unit, and the lottery makes what each gets as good as it can.

## Lotteries in general

| Word | What it means | In code | Spanish |
| --- | --- | --- | --- |
| **Participant** | Who receives in a lottery. In MTAV, a family; in Open Lottery, a person. | `participants` | |
| **Item** | What a lottery gives out. In MTAV, a unit; in Open Lottery, whatever its organizer names. | `items` | |
| **Group** | Participants and items solved together. In MTAV, a unit type. | `data` of `LotteryManifest` | |
| **Host** | What a lottery belongs to: a project or an open lottery. | `LotteryHost` | |
| **Open lottery** | A lottery with no accounts, known by its link, in the Open Lottery variant. | `OpenLottery` | |
| **Organizer** | Who created an open lottery, and alone runs it. | `organizer_key` | quien organiza |

## Life of the project

| Word | What it means | In code | Spanish |
| --- | --- | --- | --- |
| **Event** | Something on the calendar: on site, online, or the lottery itself. People answer whether they attend (RSVP). | `Event` | evento |
| **Media** | What people share. The **gallery** holds photos and videos; **files** hold documents and recordings. | `Media` | multimedia, archivos |
| **Forum** | Slow conversations among everyone, in **topics** made of **posts**. | `Topic`, `Post` | foro |
| **Chat** | Live conversations. A **conversation** is one on one or a **group**. | `Conversation`, `Message` | chat, mensajes |
| **Community** | The group every project has, with everyone in it. | `is_community` | Comunidad |
| **Assistant** | The chat's automatic guide to the app. Each person has their own conversation with it. It knows nothing about other people, and can do nothing but explain and lead. | `App\Services\Assistant` | Asistente |
| **Ready answer** | What the Assistant says with no model: greetings, thanks, the questions it offers. | `ReadyAnswers` | |
| **Notification** | What the bell shows: for one person, a project, or the superadmins. Chat messages never go there. | `Notification` | notificación |

## Help

| Word | What it means | Spanish |
| --- | --- | --- |
| **Guided tour** | Steps pointing at a page's parts. Plays by itself on a first visit, and from the (?) by each title. | recorrido guiado |
| **Guide** | The illustrated manual, one for each role. | guía |
| **FAQ** | Short answers beside the guide. | preguntas frecuentes |
