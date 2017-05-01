<template>
    <div>
        <div class="container">
            <div class="ui stackable padded grid">
                <aside class="three wide column info-column">
                    <h1>Chattoo</h1>
                    <p>
                        Simple chat app built with Laravel, Laravel ECHO, Pusher, JWT and VueJs.
                    </p>
                    <p>
                        There's a lot things to development, feel free to submit pull requests.
                    </p>
                    <div class="ui large horizontal divided list">
                        <div class="item">
                            <div class="content">
                                <a href="https://twitter.com/hserusv">
                                    <i class="twitter icon"></i> Hserusv
                                </a>
                            </div>
                        </div>
                        <div class="item">
                            <div class="content">
                                <a href="https://github.com/sureshamk/chattooo">
                                    <i class="github icon"></i>
                                    GitHub
                                </a>
                            </div>
                        </div>
                    </div>
                </aside>

                <div class="ten wide column chat-column">
                    <h2 class="ui header">
                        <i class="comments outline icon"></i>
                        <div class="content">
                            Public Chat Room
                            <div class="sub header">
                                <small v-show="chat.memberCount">
                                    Online: {{ chat.memberCount }}
                                </small>
                            </div>
                        </div>
                    </h2>

                    <div class="ui comments" id="comments">
                        <div class="ui center aligned  violet inverted segment" v-if="isUserName">
                            <div class="ui ">Welcome {{ chat.user }}  </div>
                        </div>
                        <div class="comment" v-for="message in chat.messages">
                            <div class="avatar">
                                <img width='420' height='420' v-bind:src="identicon(message.user.hashCode)">
                            </div>
                            <div class="content">
                                <span class="author">{{ message.user.name }}</span>
                                <div class="metadata">
                                    <timeago :since="message.created_at"></timeago>
                                    <div class="date" am-time-ago="message.created_at | amUtc | amLocal"></div>
                                </div>
                                <div class="text">
                                    <p>{{ message.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="chat-actions">
                        <div v-if="isUserName">
                            <form v-on:submit.stop.prevent="sendMessage()">
                                <div class="ui labeled fluid action input">
                                    <div class="ui label">
                                        {{ chat.user }} says:
                                    </div>
                                    <input placeholder="Write your message..." v-model="chat.message" required
                                           focus-on="messageReady">
                                    <button v-if="!sending" class="blue ui button" type="submit">Send &nbsp; <i class="send outline icon"></i> </button>
                                    <button v-else="" disabled=""  class="blue ui loading button">Loading</button>
                                </div>
                            </form>
                        </div>

                        <div v-else>
                            <form v-on:submit.stop.prevent="setUsername()">
                                <div class="ui fluid action input">
                                    <input placeholder="Choose your username..." v-model="chat.user"
                                           autofocus required>
                                    <button class="ui button" type="submit">Set</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <aside class="three wide column users-column">
                    <header class="filter-input">
                        <div class="ui fluid icon input">
                            <input type="text" placeholder="Filter users..." v-model="filterKeyword">
                            <i class="search icon"></i>
                        </div>
                    </header>
                    <div class="users-list">
                        <h4>Online Users</h4>
                        <div class="online-users ui tiny middle aligned list">
                            <div class="item" v-for="user in filteredUsers">
                                <div class="content">
                                    <div class="header">
                                        <img width='14' height='14' v-bind:src="identicon(user.hashCode)">
                                        {{ user.name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

</template>

<script>

    let Identicon = require('identicon.js');
    import Echo from 'laravel-echo'

    export default {
        components: {
            avtar: {
                template: ''
            }
        },
        data(){
            return {
                isUserName: false,
                sending:false,
                chat: {
                    avatar: "",
                    users: [],
                    user: "",
                    memberCount: 0,
                    message: "",
                    messages: []
                },

                user: {},
                message: {
                    user: "suresh"
                },
                enteredMessage: "",
                filterKeyword: ""
            }
        },
        mounted() {
            let that = this;
            let userName = window.sessionStorage.userName;

            if (userName) {
                this.chat.user = userName;
                this.setUsername();
            }
            axios.get('/messages')
                .then(function (response) {
                    that.chat.messages = response.data;
                    that.$nextTick(function () {
                        that.gotoBottom('comments');
                    });
                })
                .catch(function (error) {
                    console.error(error);
                });
        },
        computed: {
            filteredUsers: function () {
                let self = this;
                return self.chat.users.filter(function (user) {
                    let searchRegex = new RegExp(self.filterKeyword, 'i');
                    return searchRegex.test(user.name);
                })
            }
        },
        methods: {
            bindChannelEvents: function () {
                let that = this;

                window.Echo.join('openChat').here((users) => {
                    this.chat.users = users;
                    this.chat.memberCount = users.length;
                    window.sessionStorage.setItem('userInfo', users.user_id)
                }).listen('MessagePublished', (e) => {
                    that.chat.messages.push(e.message);
                    that.$nextTick(function () {
                        that.gotoBottom('comments');
                    });
                }).on('pusher:subscription_succeeded', (member) => {
                    if (member.me.info.token) {
                        window.sessionStorage.setItem('token', member.me.info.token);
                    }
                }).joining((user) => {
                    this.chat.users.push(user);
                    this.chat.memberCount = this.chat.memberCount + 1;
                }).leaving((user) => {
                    let cu = this.chat.users.filter(function (item) {
                        return item.user !== user.user
                    });
                    this.chat.users = cu;
                    this.chat.memberCount = this.chat.memberCount - 1;
                });
            },
            gotoBottom: function () {
                let element = document.getElementById('comments');
                element.scrollTop = element.scrollHeight - element.clientHeight;
            },
            setUsername: function () {
                window.sessionStorage.setItem('userName', this.chat.user);
                this.isUserName = true;
                const AUTH_TOKEN = 'Bearer ' + window.sessionStorage.token;
                window.Echo = new Echo({
                    broadcaster: 'pusher',
                    key: window.App.pusherKey,
                    auth: {
                        headers: {
                            'Authorization': AUTH_TOKEN
                        },
                        params: {
                            username: this.chat.user
                        }
                    }
                });
                this.bindChannelEvents();
            },
            sendMessage: function () {
                let AUTH_TOKEN = 'Bearer ' + window.sessionStorage.token;

                axios.defaults.headers.common['Authorization'] = AUTH_TOKEN;
                let that = this;
                that.sending = true;
                let msg = that.chat.message;
                that.chat.message = "";
                axios.post('/messages', {
                    message: msg,
                }).then(function (response) {
                    that.sending = false;
                }).catch(function (error) {
                    that.sending = false;
                });
            },
            identicon: function makeid(nstr) {
                return 'data:image/svg+xml;base64,' + new Identicon(nstr, {
                        size: 40,                                // 420px square
                        format: 'svg'                             // use SVG instead of PNG
                    }).toString();
            }
        }
    }
</script>
