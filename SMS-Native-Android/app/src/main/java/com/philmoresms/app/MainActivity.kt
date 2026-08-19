package com.philmoresms.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.philmoresms.app.ui.screens.bulksms.BulkSmsScreen
import com.philmoresms.app.ui.screens.home.HomeScreen
import com.philmoresms.app.ui.screens.login.LoginScreen
import com.philmoresms.app.ui.screens.otp.OtpScreen
import com.philmoresms.app.ui.screens.profile.ProfileScreen
import com.philmoresms.app.ui.screens.reports.ReportsScreen
import com.philmoresms.app.ui.screens.senderid.SenderIdScreen
import com.philmoresms.app.ui.screens.smshistory.SmsHistoryScreen
import com.philmoresms.app.ui.screens.support.SupportScreen
import com.philmoresms.app.ui.screens.support.TicketDetailScreen
import com.philmoresms.app.ui.screens.topup.TopUpScreen
import com.philmoresms.app.ui.screens.voicesms.VoiceSmsScreen
import com.philmoresms.app.ui.theme.PhilmoreTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            PhilmoreTheme {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    AppNavHost()
                }
            }
        }
    }
}

@Composable
fun AppNavHost() {
    val navController = rememberNavController()

    NavHost(navController = navController, startDestination = "login") {
        composable("login") {
            LoginScreen(onLoginSuccess = {
                navController.navigate("home") {
                    popUpTo("login") { inclusive = true }
                }
            })
        }

        composable("home") { HomeScreen(navController) }
        composable("support") { SupportScreen(navController) }
        composable("reports") { ReportsScreen(navController) }

        composable("sms_history") { SmsHistoryScreen(navController) }
        composable("profile") { ProfileScreen(navController) }
        composable("topup") { TopUpScreen(navController) }
        composable("bulk_sms") { BulkSmsScreen(navController) }
        composable("voice_sms") { VoiceSmsScreen(navController) }
        composable("otp") { OtpScreen(navController) }
        composable("sender_id") { SenderIdScreen(navController) }

        composable("ticket/{ticketId}") { backStackEntry ->
            val ticketId = backStackEntry.arguments?.getString("ticketId") ?: ""
            TicketDetailScreen(navController, ticketId)
        }
    }
}
