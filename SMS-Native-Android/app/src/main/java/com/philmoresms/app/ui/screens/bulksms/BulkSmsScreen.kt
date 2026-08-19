package com.philmoresms.app.ui.screens.bulksms

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import com.philmoresms.app.network.ErrorUtils
import com.philmoresms.app.network.RetrofitClient
import com.philmoresms.app.ui.components.AppTopBar
import com.philmoresms.app.ui.components.FintechButton
import com.philmoresms.app.ui.components.FintechInput
import com.philmoresms.app.ui.theme.*
import kotlinx.coroutines.launch
import retrofit2.HttpException
import retrofit2.await

class BulkSmsViewModel : ViewModel() {
    var senderId by mutableStateOf("")
    var recipients by mutableStateOf("")
    var message by mutableStateOf("")
    var route by mutableStateOf("promotional")
    var sending by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var success by mutableStateOf<String?>(null)

    fun send() {
        error = null
        success = null
        sending = true
        viewModelScope.launch {
            try {
                val res = RetrofitClient.apiService.sendSms(senderId, recipients, message, route).await()
                success = res.message ?: "SMS sent successfully"
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to send SMS"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                sending = false
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun BulkSmsScreen(navController: NavController) {
    val viewModel: BulkSmsViewModel = viewModel()

    Column(modifier = Modifier.fillMaxSize().background(Background)) {
        AppTopBar("Bulk SMS", onBack = { navController.popBackStack() })

        Column(
            modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp)
        ) {
            FintechInput("Sender ID", viewModel.senderId, { viewModel.senderId = it })
            FintechInput("Recipients (comma-separated)", viewModel.recipients, { viewModel.recipients = it })
            FintechInput("Message", viewModel.message, { viewModel.message = it })

            Text("Route", fontSize = 12.sp, color = TextSecondary, fontWeight = FontWeight.Bold, modifier = Modifier.padding(bottom = 8.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.padding(bottom = 20.dp)) {
                FilterChip(selected = viewModel.route == "promotional", onClick = { viewModel.route = "promotional" }, label = { Text("Promotional") })
                FilterChip(selected = viewModel.route == "corporate", onClick = { viewModel.route = "corporate" }, label = { Text("Corporate") })
            }

            if (viewModel.error != null) Text(viewModel.error ?: "", color = Danger, fontSize = 13.sp, modifier = Modifier.padding(bottom = 12.dp))
            if (viewModel.success != null) Text(viewModel.success ?: "", color = Success, fontSize = 13.sp, modifier = Modifier.padding(bottom = 12.dp))

            FintechButton(if (viewModel.sending) "Sending..." else "Send SMS", { viewModel.send() }, backgroundColor = SmsColor)
            Spacer(Modifier.height(24.dp))
        }
    }
}
