import SiteManager from "../services/site-manager";
import Auth from "../../../core/auth";

export default class Init {

    /**
     * Initialise a theme / app
     */
    public async process() {

        let result = await new Auth().ensureAuthenticated();
        if (result)
            new SiteManager().initialiseComponent(0);


    }


}