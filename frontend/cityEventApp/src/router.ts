// src/router.ts
import { createRouter, createWebHistory } from 'vue-router';
import { useAuth} from "@/useAuth";
import SignInPage from './components/SignInPage.vue';
import EventView from "./components/EventView.vue";
import registrationView from './components/RegistrationView.vue';
import BookmarkedEventsView from '@/components/BookmarkedEventsView.vue'
import PostEventView from "@/components/PostEventView.vue";
import ModeratorPendingView from "@/components/ModeratorPendingView.vue";
import ModeratorReportedView from "@/components/ModeratorReportedView.vue"
import ModeratorUsersView from "@/components/ModeratorUsersView.vue";
import SingleEventView from "@/components/SingleEventView.vue";
import ProfileView from "./components/ProfileView.vue";
import MyEventsView from "@/components/MyEventsView.vue";
import HistoricalEventView from '@/components/HistoricalEventView.vue'
import UpdateEventView from "@/components/UpdateEventView.vue";
import EventComponent from '@/components/EventComponent.vue'

const routes = [
    {
        path: '/',
        name: 'event',
        // route level code-splitting
        // this generates a separate chunk (About.[hash].js) for this route
        // which is lazy-loaded when the route is visited.
        // component: () => import('../views/AboutView.vue')
        component: EventView
    },
    {
        path: '/registration',
        name: 'registration',
        component: registrationView
    },
    {
        path: '/signin',
        name: 'signin',
        component: SignInPage
    },
    {
        path: '/bookmarks',
        name: 'bookmarks',
        component: BookmarkedEventsView,
        beforeEnter: (to, from, next) => {
            const { user } = useAuth();
            //if a user is signed in, allow them to access this route
            if (user.value)
            {
                next();
            }
            //otherwise send them to the sign in page
            else{
                next('/signin')
            }
        }
    },
    {
        path: '/historic',
        name: 'historicEvents',
        component: HistoricalEventView
    },
    {
        path: '/postevent',
        name: 'postEvent',
        component: PostEventView,
        beforeEnter: (to, from, next) => {
            const { user } = useAuth();
            //if a user is signed in, allow them to access this route
            if (user.value)
            {
                next();
            }
            //otherwise send them to the sign in page
            else{
                next('/signin')
            }
        }
    },
    {
        path: '/profile',
        name: 'profile',
        component: ProfileView,
        props: true,
        beforeEnter: (to, from, next) => {
            const { user } = useAuth();
            //if a user is signed in, or accessing a specific user's page, allow them to access this route
            if (user.value || to.query['userId'] || to.query['username'])
            {
                next();
            }
            //otherwise send them to the sign in page
            else{
                next('/signin');
            }
        }
    },
    {
        path: '/myevents',
        name: 'myEvents',
        component: MyEventsView,
        beforeEnter: (to, from, next) => {
            const { user } = useAuth();
            //if a user is signed in, allow them to access this route
            if (user.value)
            {
                next();
            }
            //otherwise send them to the sign in page
            else{
                next('/signin')
            }
        }
    },
/*    {
        path: '/myevents/past',
        name: 'myPastEvents',
        component: MyPastEventsView,
        beforeEnter: (to, from, next) => {
            const { user } = useAuth();
            //if a user is signed in, allow them to access this route
            if (user.value)
            {
                next();
            }
            //otherwise send them to the sign in page
            else{
                next('/signin')
            }
        }
    },*/
    {
        path: '/myevents/edit/:id',
        name: 'EditEvent',
        component: UpdateEventView,
        props: true,
        beforeEnter: (to, from, next) => {
            const { user } = useAuth();
            //if a user is signed in, allow them to access this route
            if (user.value)
            {
                next();
            }
            //otherwise send them to the sign in page
            else{
                next('/signin')
            }
        }
    },
    {
        path: '/moderator/pending',
        name: 'ModeratorPending',
        component: ModeratorPendingView,
        beforeEnter: (to, from, next) => {
            const { isMod } = useAuth()
            //if user is a mod let them access the route
            if(isMod.value)
            {
                next();
            }
            else{
                next('/')
            }
        }
    },
    {
        path: '/moderator/reported',
        name: 'ModeratorReported',
        component: ModeratorReportedView,
        beforeEnter: (to, from, next) => {
            const { isMod } = useAuth()
            //if user is a mod let them access the route
            if(isMod.value)
            {
                next();
            }
            else{
                next('/')
            }
        }
    },
    {
        path: '/moderator/users',
        name: 'ModeratorUsers',
        component: ModeratorUsersView,
        beforeEnter: (to, from, next) => {
            const { isMod } = useAuth()
            //if user is a mod let them access the route
            if(isMod.value)
            {
                next();
            }
            else{
                next('/')
            }
        }
    },
    {
        path: '/event/:id',
        name: 'SingleEventView',
        component: SingleEventView,
        props: true // Enables passing route params as props to the component
    },
    {
        path: '/events/interactions/interest',
        name: 'toggle_interest',
        component: EventComponent,
        props: true
    },
    {
        path: '/events/interactions/attendance',
        name: 'toggle_attendance',
        component: EventComponent,
        props: true
    }


];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
