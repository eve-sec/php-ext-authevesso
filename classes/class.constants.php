<?php
//dummy class to define constants
namespace snitch\authevesso;

define('EVESSO_SCOPES', serialize(["publicData",
                                 "esi-location.read_location.v1",
                                 "esi-skills.read_skills.v1",
                                 "esi-characters.read_contacts.v1",
                                 "esi-corporations.read_corporation_membership.v1",
                                 "esi-characters.read_standings.v1",
                                 "esi-characters.read_corporation_roles.v1",
                                 "esi-location.read_online.v1",]));


?>
