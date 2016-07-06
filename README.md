#Free and open source Placement coordination tool

#About
PlaCoTo includes a database of students, you can register with multiple groups. Users can enroll into placements and unselect deployments they don`t need and Timeframes in which they are unavailable. They can also see how many students applied for placements when choosing their preferences. Depending on their group they can use Jokers to have a date + placement preferment or priorities and location priorities, that are used to create an optimal outcome whole group.
Admins can create a placement list and add placements, that can overlap or happen at different times and in different deployments. Each placement has a name (multiple placements can share the same name if they hapen on different timeframes) and a location (used as a preferrence if the program is unable to allocate your priorities in the first place). Placement lists have two statuses (activated and not activated). Enrollment and changing prefereneces is only able with active placements. An email reminder is sent upon activating a placement list.

#Placement calculation
The program uses karma points to determine which students get placements if more than the available amount of students compete for a placement in every round. The algorithm for the generation of a (partly luck based) point system calculating the winners is in a group of students eligable (applies to the placement by Joker, current Priority or location) : x = smallest karma point in group (can be negative); y= student karma
RANDOM Number between (squareroot(median(for each student in group: abs(x)+y))) and median(for each student in group: abs(x)+y))) * y

Succesful students get a deduction of their karma points.
Students which select unavailable timeframes (coustum or calculated from the timeframes) get an initial deduction on karma points.

The program tries to allocate students first by their Joker priority (they get deducted after succesfully using them), then by their priorities (standard: 3 priorities, although they can be expanded to whatever amount you feel is right.
If the program was unable to allocate a student by Joker or Priority it uses the location preferrence to distribute users to matching to their location preference. After this users are distributed to placements with minumum places until they are met and then just randomly.

If no placement was allocated to the student for this deployment or if students didn`t pick a preference for this deployment, students get a karma bonus (set it in the config file). 

The program calculates a "happiness factor" for students, that is higher if they are allocated according to their top priorities. It also tries out random iterations for the placement (default setting is 5 can be increased to several thousands. It finally selects a table where all students are allocated and all minimum places are filled )if it can create one). The table with the highest happiness factor gets selected and if all students were allocated and minimum palces are filled emails are sent out to users otherwise, the program administrators get details about placement allocation via email.

#Email and Cronjobs
This program juses crobjobs for 1) sending of emails - as the standard php email() function is used, sending mass emails is posted to a new script that handles mass sending of emails (eg after activation of a placement), so the scripts can run flawlessly and no email gets lost. 
2) The calculation of a placement is done by a cronjonb task as well, to make it harder for administrators to influence the results of calculation.

#Install
Upload the files to your webhost, make sure the database folder is not accessable to users. Set the options in the config.php file in the /site folder according to your preferences (setting up a recaptcha API is recommended to prevent user-spam).
You can login (http://yoursite.com/admin.php) with the account "ADMIN" password "ADMIN" after uploading the files and get started.
Groups have to be manually set by editing the groups.json file in /database now, but this feature will be released soon.

Set up two cronjobs: 1 linking to your calculate_placements.php (I would recommend setting the cronjob timer to 00:01 every day, as webserver traffic will be low and running this script locks the preferences process). Set another cronjob to run send_email_queue.php a couple of minutes after the calculate_placements.php cronjob was executed. You can also manually execute both cronjobs. They are likned from the admin panel.

Have fun!
